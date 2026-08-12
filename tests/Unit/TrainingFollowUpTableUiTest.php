<?php

namespace Tests\Unit;

use Tests\TestCase;

class TrainingFollowUpTableUiTest extends TestCase
{
    public function test_category_is_the_first_business_column_and_table_has_correct_colspan(): void
    {
        $blade = file_get_contents(
            resource_path('views/people_development/edit_develop_hrga.blade.php'),
        );
        $header = substr(
            $blade,
            strpos($blade, '<table id="summary-table"'),
            strpos($blade, '</thead>') - strpos($blade, '<table id="summary-table"'),
        );

        $numberPosition = strpos($header, '>No</th>');
        $categoryPosition = strpos($header, '>Kategori Usulan</th>');
        $sectionPosition = strpos($header, '>Section</th>');

        $this->assertIsInt($numberPosition);
        $this->assertIsInt($categoryPosition);
        $this->assertIsInt($sectionPosition);
        $this->assertLessThan($categoryPosition, $numberPosition);
        $this->assertLessThan($sectionPosition, $categoryPosition);
        $this->assertStringContainsString('colspan="21"', $blade);
        $this->assertStringNotContainsString('colspan="19"', $blade);
    }

    public function test_table_sharing_knowledge_uses_a_synchronized_multi_select(): void
    {
        $blade = file_get_contents(
            resource_path('views/people_development/edit_develop_hrga.blade.php'),
        );

        $this->assertStringContainsString('participantSelect.multiple = true;', $blade);
        $this->assertStringContainsString(
            '(values) => syncInlineParticipantsToCard(id, values)',
            $blade,
        );
        $this->assertStringContainsString(
            "renderInlineEmployeeControl(id, katUsulanSel.value === '1');",
            $blade,
        );
    }

    public function test_table_scroll_chains_vertically_but_remains_contained_horizontally(): void
    {
        $css = file_get_contents(public_path('css/hr/training-follow-up.css'));

        $this->assertStringContainsString('overscroll-behavior-x: contain;', $css);
        $this->assertStringContainsString('overscroll-behavior-y: auto;', $css);
        $this->assertStringNotContainsString('overscroll-behavior: contain;', $css);
    }

    public function test_submission_ignores_inert_rows_and_exposes_validation_details(): void
    {
        $blade = file_get_contents(
            resource_path('views/people_development/edit_develop_hrga.blade.php'),
        );

        $this->assertStringContainsString('shouldIgnoreInertFollowUpRow(row)', $blade);
        $this->assertStringContainsString('buildSubmissionError(xhr, submittedRows)', $blade);
        $this->assertStringContainsString('Data Belum Dapat Disimpan', $blade);
        $this->assertStringContainsString("jumpToCard('row-' + failure.firstInvalidRowId)", $blade);
    }
}
