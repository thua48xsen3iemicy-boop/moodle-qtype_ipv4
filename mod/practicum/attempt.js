// Moodle plugin, GPL v3 or later.
/* global M */
M.mod_practicum = M.mod_practicum || {};

/** Timed assessments: serialize saves, freeze at zero, submit the final snapshot. */
M.mod_practicum.init = function(Y, seconds) {
    const form = document.getElementById('practicum-attempt');
    const timer = document.getElementById('practicum-timeleft');
    const status = document.getElementById('practicum-savestatus');
    const started = performance.now();
    const wallStarted = Date.now();
    let pending = null;
    let dirty = false;
    let finishing = false;
    let debounce;
    let finalData;
    const sequence = {};
    const message = key => { status.textContent = status.dataset[key]; };
    const freeze = () => {
        // Capture before disabling controls (disabled values are omitted from FormData).
        finalData = new FormData(form);
        form.querySelectorAll('input:not([type="hidden"]), textarea, select, button').forEach(control => {
            control.disabled = true;
        });
        finishing = true;
        clearTimeout(debounce);
    };

    const save = async(data, finish) => {
        // Never overlap question-engine actions: each response supplies the next sequence checks.
        if (pending) {
            await pending;
        }
        Object.entries(sequence).forEach(([name, value]) => data.set(name, value));
        data.set('autosave', '1');
        if (finish) {
            data.set('finish', '1');
        }
        message(finish ? 'timeup' : 'saving');
        pending = (async() => {
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 8000);
            try {
                const response = await fetch(form.action, {
                    method: 'POST', body: data, credentials: 'same-origin', signal: controller.signal
                });
                if (!response.ok) {
                    throw new Error('Save failed');
                }
                const result = await response.json();
                if (typeof result.finished !== 'boolean' || !result.sequence) {
                    throw new Error('Invalid save response');
                }
                Object.assign(sequence, result.sequence);
                Object.entries(sequence).forEach(([name, value]) => {
                    const field = form.elements.namedItem(name);
                    if (field) {
                        field.value = value;
                    }
                });
                if (result.finished) {
                    window.location.assign(form.action);
                } else {
                    message(dirty ? 'saving' : 'saved');
                    if (dirty && !finishing) {
                        setTimeout(autosave, 0);
                    }
                }
            } catch (error) {
                message('saveerror');
                dirty = true;
                if (finish) {
                    // Retry the frozen payload, never collect answers entered after zero.
                    setTimeout(() => save(finalData, true), 5000);
                }
            } finally {
                clearTimeout(timeout);
            }
        })();
        await pending;
        pending = null;
    };

    const autosave = () => {
        if (!finishing && dirty && !pending) {
            dirty = false;
            void save(new FormData(form), false);
        }
    };
    const changed = () => {
        dirty = true;
        message('saving');
        clearTimeout(debounce);
        debounce = setTimeout(autosave, 700);
    };
    form.addEventListener('input', changed);
    form.addEventListener('change', changed);
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (finishing) {
            return;
        }
        if (event.submitter && event.submitter.name === 'finish') {
            freeze();
            void save(finalData, true);
        } else {
            dirty = true;
            autosave();
        }
    });
    const tick = () => {
        const elapsed = Math.max(performance.now() - started, Date.now() - wallStarted);
        const remaining = Math.max(0, Math.ceil(seconds - elapsed / 1000));
        timer.textContent = Math.floor(remaining / 60) + ':' + String(remaining % 60).padStart(2, '0');
        if (remaining === 0 && !finishing) {
            freeze();
            void save(finalData, true);
        }
    };
    setInterval(tick, 250);
    setInterval(autosave, 10000);
    document.addEventListener('visibilitychange', tick);
    tick();
};
