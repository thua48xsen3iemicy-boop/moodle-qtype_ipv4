<?php
// Moodle plugin, GPL v3 or later.
namespace qtype_linuxcmd;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/question/type/linuxcmd/question.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\qtype_linuxcmd_question::class)]
final class question_test extends \basic_testcase {
    public function test_saved_condition_survives_changed_template_and_regrades(): void {
        foreach (exercise::TYPES as $type) {
            foreach (exercise::MODES as $mode) {
                $question = new \qtype_linuxcmd_question();
                $question->tasktype = $type;
                $question->pathmode = $mode;
                $step = new \question_attempt_step();
                $question->start_attempt($step, 1);
                $snapshot = $question->exercise;
                $response = $question->get_correct_response();
                $question->tasktype = 'rmdir';
                $question->pathmode = 'absolute';
                $question->apply_attempt_state($step);
                $this->assertSame($snapshot, $question->exercise);
                $this->assertSame($response, $question->get_correct_response());
                $this->assertSame(1.0, $question->grade_response($response)[0]);
                $this->assertSame(0.0, $question->grade_response(['answer' => 'pwd'])[0]);
                $this->assertFalse($question->is_complete_response([]));
            }
        }
    }
}
