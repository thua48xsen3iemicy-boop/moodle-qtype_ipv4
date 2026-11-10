<?php
// GPL v3 or later.
namespace mod_practicum;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/practicum/lib.php');
/** Tests activity policies against the real question engine. */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_practicum\local\attempts::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_practicum\local\blueprint::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_practicum\local\attempt_navigation::class)]
final class lifecycle_test extends \advanced_testcase {
    private function setup_activity(string $mode = 'exam'): array {
        $this->resetAfterTest(); $this->setAdminUser();
        $g=$this->getDataGenerator(); $course=$g->create_course();
        $p=$g->create_module('practicum', ['course'=>$course->id,'mode'=>$mode,'timelimit'=>60,
            'blockcount'=>[2,1], 'blocktype'=>['ipv4_network','ipv4_network'], 'blockprefix'=>['24','8,16']]);
        $u=$g->create_user();$g->enrol_user($u->id,$course->id,'student');$this->setUser($u);
        return [$p,$u,\context_module::instance($p->cmid)];
    }
    public function test_exam_snapshot_resume_grade_and_limit(): void {
        global $DB;
        [$p,$u,$c]=$this->setup_activity();
        $a=local\attempts::start($p,$c,$u->id);
        $this->assertEquals($a->id,local\attempts::start($p,$c,$u->id)->id);
        $usage=\question_engine::load_questions_usage_by_activity($a->usageid);
        $this->assertCount(3,$usage->get_slots());
        $snapshot=[];
        foreach($usage->get_slots() as $slot){
            $q=$usage->get_question($slot);$snapshot[]=$q->ip.'/'.$q->prefix;
            $this->assertSame('text',$q->answermode);
            $usage->process_action($slot,$q->get_correct_response());
        }
        \question_engine::save_questions_usage_by_activity($usage);
        $DB->set_field('practicum','mode','training',['id'=>$p->id]);
        $a=local\attempts::process($a->id,null,true);
        $this->assertEquals(100,$a->grade);
        $this->assertSame('exam',$a->mode);
        $loaded=\question_engine::load_questions_usage_by_activity($a->usageid);
        $this->assertSame($snapshot,array_map(fn($s)=>$loaded->get_question($s)->ip.'/'.$loaded->get_question($s)->prefix,$loaded->get_slots()));
        $grade=$DB->get_record_sql('SELECT g.* FROM {grade_grades} g JOIN {grade_items} i ON i.id=g.itemid WHERE i.itemmodule=? AND i.iteminstance=? AND g.userid=?',['practicum',$p->id,$u->id]);
        $this->assertEquals(100,$grade->rawgrade);
        $this->expectException(\moodle_exception::class);
        local\attempts::start($p,$c,$u->id);
    }
    public function test_training_check_returns_to_question_but_finish_returns_to_result(): void {
        [$p, $user, $context] = $this->setup_activity('training');
        $attempt = local\attempts::start($p, $context, $user->id);
        $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
        $slots = $usage->get_slots();
        $question = $usage->get_question_attempt(end($slots));
        $post = [$question->get_behaviour_field_name('submit') => 1];
        $url = local\attempt_navigation::after_submit($attempt, $post);
        $this->assertSame($question->get_outer_question_div_unique_id(), parse_url($url->out(false), PHP_URL_FRAGMENT));
        $this->assertEquals($attempt->id, $url->param('id'));
        $this->assertSame('', parse_url(local\attempt_navigation::after_submit($attempt, [])->out(false), PHP_URL_FRAGMENT));
        $this->assertSame('', parse_url(local\attempt_navigation::after_submit($attempt,
            ['q999999:999_-submit' => 1])->out(false), PHP_URL_FRAGMENT));
        $finished = local\attempts::process($attempt->id, null, true);
        $this->assertSame('', parse_url(local\attempt_navigation::after_submit($finished, $post)->out(false), PHP_URL_FRAGMENT));
        $attempt->mode = 'exam';
        $this->assertSame('', parse_url(local\attempt_navigation::after_submit($attempt, $post)->out(false), PHP_URL_FRAGMENT));
    }

    public function test_random_pool_resume_keeps_plan_after_configuration_edit(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $p = $g->create_module('practicum', [
            'course' => $course->id, 'mode' => 'training', 'selectionmode' => 'random',
            'selectedgroups' => ['files' => 1, 'routing' => 1], 'sharedcount' => 6,
            'sharedprefix' => [26], 'sharedpathmode' => 'mixed',
        ]);
        $student = $g->create_user();
        $g->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $context = \context_module::instance($p->cmid);
        $attempt = local\attempts::start($p, $context, $student->id);
        $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
        $this->assertCount(6, $usage->get_slots());
        $plan = $attempt->plan;
        $p->blocks = local\blueprint::from_form((object)[
            'selectionmode' => 'each', 'selectedgroups' => ['subnet' => 1],
            'sharedcount' => 1, 'sharedprefix' => [24],
        ]);
        $DB->set_field('practicum', 'blocks', $p->blocks, ['id' => $p->id]);
        $resumed = local\attempts::start($p, $context, $student->id);
        $this->assertEquals($attempt->id, $resumed->id);
        $this->assertSame($plan, $resumed->plan);
        foreach ($usage->get_slots() as $slot) {
            $usage->process_action($slot, $usage->get_question($slot)->get_correct_response());
        }
        \question_engine::save_questions_usage_by_activity($usage);
        $finished = local\attempts::process($attempt->id, null, true);
        $this->assertEquals(100, $finished->grade);
        $next = local\attempts::start($p, $context, $student->id);
        $this->assertCount(count(local\registry::groups()['subnet']), json_decode($next->plan, true));
    }

    public function test_multiselect_blocks_resume_and_grade_with_per_type_counts(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $g = $this->getDataGenerator();
        $course = $g->create_course();
        $p = $g->create_module('practicum', [
            'course' => $course->id, 'mode' => 'training', 'selectionmode' => 'advanced',
            'blocktype' => [['ipv4_network', 'linux_move'], ['ipv4_hostcount']],
            'blockcount' => [2, 3], 'blockprefix' => [[26], [24]],
        ]);
        $student = $g->create_user();
        $g->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $context = \context_module::instance($p->cmid);
        $attempt = local\attempts::start($p, $context, $student->id);
        $plan = json_decode($attempt->plan, true);
        $counts = array_count_values(array_column($plan, 'type'));
        $this->assertSame(2, $counts['ipv4_network']);
        $this->assertSame(2, $counts['linux_move']);
        $this->assertSame(3, $counts['ipv4_hostcount']);
        $p->blocks = local\blueprint::from_form((object)[
            'blocktype' => [['linux_touch', 'linux_mkdir']], 'blockcount' => [1],
        ]);
        $DB->set_field('practicum', 'blocks', $p->blocks, ['id' => $p->id]);
        $resumed = local\attempts::start($p, $context, $student->id);
        $this->assertEquals($attempt->id, $resumed->id);
        $this->assertSame($attempt->plan, $resumed->plan);
        $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
        foreach ($usage->get_slots() as $slot) {
            $usage->process_action($slot, $usage->get_question($slot)->get_correct_response() + ['-submit' => 1]);
        }
        \question_engine::save_questions_usage_by_activity($usage);
        $this->assertEquals(100, local\attempts::process($attempt->id, null, true)->grade);
    }

    public function test_training_is_untimed_and_does_not_gradebook(): void {
        global $DB;
        [$p,$u,$c]=$this->setup_activity('training');
        $a=local\attempts::start($p,$c,$u->id);
        $this->assertEquals(0,$a->deadline);
        $usage=\question_engine::load_questions_usage_by_activity($a->usageid);
        $this->assertSame('immediatefeedback',$usage->get_preferred_behaviour());
        local\attempts::process($a->id,null,true);
        $next=local\attempts::start($p,$c,$u->id);
        $this->assertNotEquals($a->id,$next->id);
        $this->assertSame(0,$DB->count_records('grade_grades',['userid'=>$u->id]));
    }
    public function test_deadline_finishes_saved_answers_and_rejects_late_data(): void {
        global $DB;
        [$p,$u,$c]=$this->setup_activity();$a=local\attempts::start($p,$c,$u->id);
        $usage=\question_engine::load_questions_usage_by_activity($a->usageid);
        $post=['slots'=>implode(',',$usage->get_slots())];
        foreach($usage->get_slots() as $slot){
            $qa=$usage->get_question_attempt($slot);
            $post[$qa->get_qt_field_name('answer')]=$qa->get_question()->get_correct_response()['answer'];
        }
        $DB->set_field('practicum_attempts','deadline',time()-1,['id'=>$a->id]);
        $a=local\attempts::process($a->id,$post);
        $this->assertGreaterThan(0,$a->timefinish);$this->assertEquals(0,$a->grade);
        $this->assertEquals($a,local\attempts::process($a->id,$post,true));
    }
    public function test_once_all_ipv4_categories_resume_and_grade_in_both_modes(): void {
        global $DB;
        $this->resetAfterTest();
        foreach (['training', 'exam'] as $mode) {
            $this->setAdminUser();
            $g = $this->getDataGenerator();
            $course = $g->create_course();
            $types = array_map(fn($type) => 'ipv4_' . $type, \qtype_ipv4\exercise::ALL_TYPES);
            $p = $g->create_module('practicum', [
                'course' => $course->id, 'mode' => $mode, 'selectionmode' => 'once',
                'selectedgroups' => array_fill_keys(array_keys(\qtype_ipv4\exercise\catalog::groups()), 1),
                'sharedprefix' => [26],
            ]);
            $student = $g->create_user();
            $g->enrol_user($student->id, $course->id, 'student');
            $this->setUser($student);
            $context = \context_module::instance($p->cmid);
            $attempt = local\attempts::start($p, $context, $student->id);
            $usage = \question_engine::load_questions_usage_by_activity($attempt->usageid);
            $this->assertCount(count($types), $usage->get_slots());
            $seen = [];
            $snapshots = [];
            foreach ($usage->get_slots() as $slot) {
                $question = $usage->get_question($slot);
                $seen[] = 'ipv4_' . $question->tasktype;
                if (in_array($question->tasktype, \qtype_ipv4\exercise::SUBNET_TYPES, true)) {
                    $this->assertSame(26, $question->prefix);
                }
                $boolean = in_array($question->tasktype, ['samesubnet', 'assignable'], true);
                $this->assertSame($boolean ? 'choice' : 'text', $question->answermode);
                if ($boolean) {
                    $this->assertEqualsCanonicalizing(['yes', 'no'], $question->choices);
                    $this->assertFalse($question->is_complete_response([]));
                    foreach (['yes', 'no'] as $answer) {
                        $expected = $answer === $question->get_correct_response()['answer'] ? 1.0 : 0.0;
                        $this->assertSame($expected, $question->grade_response(['answer' => $answer])[0]);
                    }
                }
                $this->assertStringNotContainsString('[[', $question->task_text());
                $snapshots[$slot] = $question->exercise;
                $response = $question->get_correct_response();
                if ($mode === 'training') {
                    $response['-submit'] = 1;
                }
                $usage->process_action($slot, $response);
            }
            $this->assertEqualsCanonicalizing($types, $seen);
            \question_engine::save_questions_usage_by_activity($usage);
            // Edits to activity settings must not change a running attempt.
            $DB->set_field('practicum', 'blocks', json_encode([
                ['type' => 'ipv4_network', 'count' => 1, 'prefixes' => [8]],
            ]), ['id' => $p->id]);
            $changed = $DB->get_record('practicum', ['id' => $p->id]);
            $this->assertEquals($attempt->id, local\attempts::start($changed, $context, $student->id)->id);
            $reloaded = \question_engine::load_questions_usage_by_activity($attempt->usageid);
            foreach ($reloaded->get_slots() as $slot) {
                $this->assertSame($snapshots[$slot], $reloaded->get_question($slot)->exercise);
            }
            $finished = local\attempts::process($attempt->id, null, true);
            $this->assertEquals(100, $finished->grade);
        }
    }

    public function test_unknown_family_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        local\blueprint::plan([['type'=>'shell_exec','count'=>1,'prefixes'=>[24]]]);
    }
    public function test_unauthorised_user_cannot_start(): void {
        [$p,$u,$c]=$this->setup_activity();$other=$this->getDataGenerator()->create_user();
        $this->expectException(\required_capability_exception::class);
        local\attempts::start($p,$c,$other->id);
    }
    public function test_privacy_deletes_owned_attempt_and_usage(): void {
        global $DB;
        [$p,$u,$c]=$this->setup_activity();$a=local\attempts::start($p,$c,$u->id);
        $contexts=\mod_practicum\privacy\provider::get_contexts_for_userid($u->id);
        $this->assertContains((int)$c->id,array_map('intval',$contexts->get_contextids()));
        $approved=new \core_privacy\local\request\approved_contextlist($u,'mod_practicum',[$c->id]);
        \mod_practicum\privacy\provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('practicum_attempts',['id'=>$a->id]));
        $this->assertFalse($DB->record_exists('question_usages',['id'=>$a->usageid]));
    }
    public function test_backup_restore_settings_and_finished_attempt(): void {
        global $CFG,$DB,$USER;
        require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');
        [$p,$u,$c]=$this->setup_activity();$a=local\attempts::start($p,$c,$u->id);
        local\attempts::process($a->id,null,true);$this->setAdminUser();
        $target=$this->getDataGenerator()->create_course();
        $bc=new \backup_controller(\backup::TYPE_1COURSE,$p->course,\backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,\backup::MODE_GENERAL,$USER->id);
        $bc->get_plan()->get_setting('users')->set_value(true);
        $backupid=$bc->get_backupid();$bc->execute_plan();$results=$bc->get_results();$bc->destroy();
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
            $results['backup_destination'],make_backup_temp_directory($backupid));
        $rc=new \restore_controller($backupid,$target->id,\backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,$USER->id,\backup::TARGET_CURRENT_ADDING);
        $this->assertTrue($rc->execute_precheck());$rc->execute_plan();$rc->destroy();
        $restored=$DB->get_record('practicum',['course'=>$target->id],'*',MUST_EXIST);
        $this->assertSame($p->blocks,$restored->blocks);
        $ra=$DB->get_record('practicum_attempts',['practicumid'=>$restored->id],'*',MUST_EXIST);
        $original=\question_engine::load_questions_usage_by_activity($a->usageid);
        $copy=\question_engine::load_questions_usage_by_activity($ra->usageid);
        $this->assertCount(3,$copy->get_slots());
        foreach($copy->get_slots() as $slot){
            $this->assertSame($original->get_question($slot)->ip,$copy->get_question($slot)->ip);
        }
    }
}
