#!/usr/bin/env python3
"""Exercise the shipped timer in Chromium against a deterministic HTTP fixture.

Requires Python Playwright and Chromium. This is not a Moodle integration test.
Run: python tools/check-practicum-timer.py
"""
import asyncio
from email.parser import BytesParser
from email.policy import default
from pathlib import Path
from urllib.parse import parse_qsl
from playwright.async_api import async_playwright

SCRIPT = Path(__file__).resolve().parents[1] / 'mod/practicum/attempt.js'
HTML = '''<!doctype html><html><body>
<div id="practicum-timeleft" role="timer"></div>
<p id="practicum-savestatus" data-saving="saving" data-saved="saved"
data-saveerror="error" data-timeup="submitting"></p>
<form id="practicum-attempt" action="http://practicum.test/attempt" method="post">
<input name="id" type="hidden" value="1"><input name="sesskey" type="hidden" value="fixture">
<input name="slots" type="hidden" value="1">
<input name="q1:1_:sequencecheck" type="hidden" value="0">
<input name="q1:1_answer" aria-label="answer" value="">
<button type="submit">Save</button><button name="finish" value="1" type="submit">Finish</button>
<button name="q1:1_-submit" value="1" type="submit">Check</button>
</form><script>window.M = {};</script></body></html>'''


async def scenario(browser, name, seconds, delay=0, fail=False, manual=False, savedonly=False, lostack=False, check=False):
    page = await browser.new_page()
    requests = []
    sequence = 0
    active = 0
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))

    async def route_request(route):
        nonlocal sequence, active, fail, lostack
        request = route.request
        if request.method == 'GET':
            await route.fulfill(content_type='text/html', body=HTML if not requests else '<p>Result</p>')
            return
        raw = ('Content-Type: ' + request.headers['content-type'] + '\r\n\r\n').encode() + request.post_data_buffer
        message = BytesParser(policy=default).parsebytes(raw)
        if message.is_multipart():
            data = {part.get_param('name', header='content-disposition'): part.get_content()
                    for part in message.iter_parts()}
        else:
            data = dict(parse_qsl(request.post_data))
        active += 1
        assert active == 1, 'Overlapping question-engine POSTs'
        requests.append(data)
        if fail and data.get('finish') == '1':
            fail = False
            active -= 1
            await route.abort('failed')
            return
        assert data['q1:1_:sequencecheck'] == str(sequence), data
        assert data['sesskey'] == 'fixture'
        if delay and len(requests) == 1:
            await asyncio.sleep(delay)
        # Moodle's native autosave keeps the real-step sequence check unchanged.
        if data.get('finish') == '1':
            sequence += 1
        active -= 1
        if lostack and data.get('finish') != '1':
            lostack = False
            await route.abort('failed')
            return
        if data.get('q1:1_-submit') == '1':
            assert 'autosave' not in data, data
            await route.fulfill(content_type='text/html', body='<p>Result</p>')
        else:
            await route.fulfill(json={'finished': data.get('finish') == '1',
                                     'sequence': {'q1:1_:sequencecheck': sequence}})

    await page.route('http://practicum.test/**', route_request)
    await page.goto('http://practicum.test/attempt')
    await page.add_script_tag(path=str(SCRIPT))
    if check:
        # Match Moodle's repeat-submit guard, which has already seen the intercepted event.
        await page.evaluate('''() => {
            const form = document.querySelector('form');
            form.addEventListener('submit', event => {
                if (form.dataset.formSubmitted === '1') { event.preventDefault(); }
                form.dataset.formSubmitted = '1';
                setTimeout(() => {
                    form.querySelector('[name="q1:1_-submit"]').disabled = true;
                }, 0);
            });
        }''')
    if savedonly:
        await page.evaluate('''() => {
            const form = document.querySelector('form');
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'finishsaved'; hidden.value = '1'; form.append(hidden);
            document.querySelector('[aria-label="answer"]').disabled = true;
        }''')
    # Moodle js_init_call prepends its YUI instance to the supplied arguments.
    await page.evaluate('(seconds) => M.mod_practicum.init(null, seconds)', seconds)
    if seconds:
        assert await page.locator('[role="timer"]').inner_text() == str(seconds // 60) + ':' + str(seconds % 60).zfill(2)
    if seconds:
        await page.get_by_label('answer').fill('first answer')
    if delay or manual:
        await page.wait_for_timeout(900)
        await page.get_by_label('answer').fill('final answer')
    if manual:
        await page.get_by_role('button', name='Finish', exact=True).click()
    if check:
        await page.get_by_role('button', name='Check', exact=True).click()
    if (delay or manual) and not check:
        await page.wait_for_function('document.querySelector("[aria-label=answer]").disabled')
    await page.get_by_text('Result', exact=True).wait_for(timeout=15000)
    assert not errors, errors
    final = requests[-1]
    if check:
        assert final['q1:1_-submit'] == '1' and 'autosave' not in final, requests
    else:
        assert final['finish'] == '1', requests
    if savedonly:
        assert final['finishsaved'] == '1' and 'q1:1_answer' not in final
    else:
        assert final['q1:1_answer'] == ('final answer' if delay or manual else 'first answer'), requests
    if delay or manual:
        assert len(requests) >= 2, requests
        assert final['q1:1_:sequencecheck'] == '0', requests
    print(name + ': passed (' + str(len(requests)) + ' POSTs)')
    await page.close()


async def main():
    async with async_playwright() as playwright:
        browser = await playwright.chromium.launch(executable_path='/usr/bin/chromium', args=['--no-sandbox'])
        await scenario(browser, 'Countdown and automatic final submission', 2)
        await scenario(browser, 'Expiry waits for autosave and sends latest answers', 2, delay=2)
        await scenario(browser, 'Manual finish waits for autosave', 20, delay=2, manual=True)
        await scenario(browser, 'Failed final submission retries the captured answers', 2, fail=True)
        await scenario(browser, 'Lost autosave acknowledgement preserves final submission', 2, lostack=True)
        await scenario(browser, 'Failed final submission retries frozen answers', 0, fail=True, savedonly=True)
        await scenario(browser, 'Expired readonly page finishes stored answers', 0, savedonly=True)
        await scenario(browser, 'Timed training Check waits for autosave then posts natively', 20, delay=2, check=True)
        await browser.close()


if __name__ == '__main__':
    asyncio.run(main())
