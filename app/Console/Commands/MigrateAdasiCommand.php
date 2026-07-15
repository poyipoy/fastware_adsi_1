<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MstDepartment;
use App\Models\MstSection;
use App\Models\MstJobPosition;
use App\Models\MstPositionApproval;
use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateAdasiCommand extends Command
{
    protected $signature = 'app:migrate-adasi {--dry-run : Preview changes without committing}';
    protected $description = 'Migrate ADASI Master Data based on Excel JSON dump';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $jsonPath = base_path('parse_result.json');
        if (!File::exists($jsonPath)) {
            $this->error('File parse_result.json not found!');
            return 1;
        }

        $data = json_decode(File::get($jsonPath), true);
        
        $this->info("Starting ADASI Master Data Migration" . ($dryRun ? " [DRY RUN]" : ""));

        DB::beginTransaction();
        try {
            // STEP 1: Migrate Departments & Sections
            $officialSections = $data['official_sections'];
            $activeSectionIds = [];
            $activeDeptIds = [];
            
            $departments = [];
            foreach ($officialSections as $sec) {
                $deptName = $this->normalizeName($sec['dept']);
                if (!in_array($deptName, $departments)) {
                    $departments[] = $deptName;
                }
            }

            foreach ($departments as $deptName) {
                $dept = MstDepartment::where('name', $deptName)->first();
                if (!$dept) {
                    $this->info("Creating Department: $deptName");
                    if (!$dryRun) {
                        $dept = MstDepartment::create(['name' => $deptName, 'is_active' => true]);
                    } else {
                        $dept = new MstDepartment(['name' => $deptName, 'is_active' => true]);
                        $dept->id = rand(1000, 9000); 
                    }
                } else {
                    if (!$dept->is_active) {
                        $this->info("Re-activating Department: $deptName");
                        if (!$dryRun) {
                            $dept->update(['is_active' => true]);
                        }
                    }
                }
                if ($dept) $activeDeptIds[$deptName] = $dept->id ?? $deptName;
            }

            foreach ($officialSections as $sec) {
                $deptName = $this->normalizeName($sec['dept']);
                $secName = $this->normalizeName($sec['section']);
                
                $deptId = $dryRun ? ($activeDeptIds[$deptName] ?? 9999) : MstDepartment::where('name', $deptName)->value('id');

                $section = MstSection::where('name', $secName)->first();
                if (!$section) {
                    $this->info("Creating Section: $secName (Dept: $deptName)");
                    if (!$dryRun) {
                        $section = MstSection::create(['department_id' => $deptId, 'name' => $secName, 'is_active' => true]);
                    } else {
                        $section = new MstSection(['department_id' => $deptId, 'name' => $secName, 'is_active' => true]);
                        $section->id = rand(1000, 9000);
                    }
                } else {
                    if ($section->department_id != $deptId || !$section->is_active) {
                        $this->info("Updating Section: $secName -> New Dept ID: $deptId");
                        if (!$dryRun) {
                            $section->update(['department_id' => $deptId, 'is_active' => true]);
                        }
                    }
                }
                if ($section) $activeSectionIds[$secName] = $section->id ?? $secName;
            }

            // Deactivate old departments and sections
            if (!$dryRun) {
                $deactivatedDepts = MstDepartment::whereNotIn('id', array_values($activeDeptIds))->update(['is_active' => false]);
                $deactivatedSecs = MstSection::whereNotIn('id', array_values($activeSectionIds))->update(['is_active' => false]);
                $this->info("Deactivated $deactivatedDepts old departments and $deactivatedSecs old sections.");
            } else {
                $this->info("Will deactivate old departments and sections not in the list.");
            }

            // STEP 2: Job Positions & Employee Mappings
            $employees = $data['employees'];
            $processedEmpNames = [];
            
            $jobPositionsCache = []; // to store created MstJobPosition ids
            
            $userAliases = [
                'RICHARDUS CHRISTIAN' => 'RICHARDUS CHRISTIAN',
                'RAIHAN GILANG RAMADHAN' => 'RAIHAN',
            ];
            $activeJobPositionIds = [];
            
            // First pass: Create all Job Positions and assign to users
            foreach ($employees as $emp) {
                $rawName = strtoupper(trim($emp['nama']));
                
                // Deduplicate Yan Welem Manginsela
                if (in_array($rawName, $processedEmpNames)) {
                    $this->line("Skipping duplicate employee: $rawName");
                    continue;
                }
                $processedEmpNames[] = $rawName;

                $searchName = $userAliases[$rawName] ?? $rawName;
                $user = User::where('name', 'LIKE', "%{$searchName}%")->first();
                if (!$user) {
                    $nameParts = explode(' ', trim($rawName));
                    $baseUsername = strtolower($nameParts[0]);
                    
                    $username = $baseUsername;
                    $counter = 1;
                    while (User::where('username', $username)->exists()) {
                        $username = $baseUsername . $counter;
                        $counter++;
                    }

                    $this->info("User not found: $rawName. Auto-creating user with username: $username");

                    if (!$dryRun) {
                        $user = User::create([
                            'name' => $rawName,
                            'username' => $username,
                            'password' => bcrypt('12345'),
                            'pass' => '12345',
                            'is_active' => true,
                            'npk' => '0',
                        ]);
                    } else {
                        $user = new User([
                            'name' => $rawName,
                            'username' => $username,
                            'is_active' => true,
                        ]);
                        $user->id = rand(1000, 9999);
                    }
                }
                
                $this->info("Processing User: $rawName");

                // Clear existing job positions if not dry run
                if (!$dryRun) {
                    UserJobPosition::where('user_id', $user->id)->update(['is_active' => false]);
                }

                $jobTitleLines = explode("\n", trim($emp['job_position']));
                $secLines = explode("\n", trim($emp['section_job']));
                $deptLines = explode("\n", trim($emp['dept_job']));
                
                if ($rawName === 'HARDI SAPUTRA') {
                    $deptLines = ['Sales Region 1 & 2', 'Sales Region 3 & 4', 'Logistic'];
                    // We map his job titles to each department
                    $jobTitleLines = ['Sales Div Head Region 1 & 2', 'Sales Div Head Region 3 & 4', 'Logistic Dept Head'];
                    $secLines = [null, null, null];
                }

                $maxLines = max(count($jobTitleLines), count($secLines), count($deptLines));

                for ($i = 0; $i < $maxLines; $i++) {
                    $jpName = $this->normalizeName($jobTitleLines[$i] ?? $jobTitleLines[0]);
                    $sName = $this->normalizeName($secLines[$i] ?? $secLines[0]);
                    $dName = $this->normalizeName($deptLines[$i] ?? $deptLines[0]);

                    // Map legacy names
                    if ($dName == 'Logistic & Warehouse') $dName = 'Logistic';
                    if ($dName == 'PDCA, Proc, Inv & IT') $dName = 'PDCA, Inventory, Procurement & IT';
                    if ($sName == 'Logistic & Warehouse') $sName = 'Logistic';

                    $sId = null;
                    if ($sName) {
                        $sId = $dryRun ? ($activeSectionIds[$sName] ?? null) : MstSection::where('name', $sName)->value('id');
                    }
                    $dId = null;
                    if ($dName) {
                        $dId = $dryRun ? ($activeDeptIds[$dName] ?? null) : MstDepartment::where('name', $dName)->value('id');
                    }

                    if (!$dryRun) {
                        $jobPos = MstJobPosition::updateOrCreate(
                            ['position_name' => $jpName],
                            [
                                'department_id' => $dId,
                                'section_id' => $sId,
                                'is_active' => true
                            ]
                        );
                        $jobPositionsCache[$rawName][] = $jobPos;
                        $activeJobPositionIds[] = $jobPos->id;
                        
                        // Assign user to job position
                        UserJobPosition::updateOrCreate(
                            ['user_id' => $user->id, 'mst_job_position_id' => $jobPos->id],
                            ['is_active' => true]
                        );
                    } else {
                        $this->line("  -> Maps to Job Position: $jpName (Sec: $sName, Dept: $dName)");
                        $fakeJobPos = new MstJobPosition(['position_name' => $jpName, 'department_id' => $dId, 'section_id' => $sId]);
                        $fakeJobPos->id = rand(1, 9999);
                        $jobPositionsCache[$rawName][] = $fakeJobPos;
                    }
                }
            }

            // Deactivate old job positions
            if (!$dryRun && !empty($activeJobPositionIds)) {
                $deactivatedJobs = MstJobPosition::whereNotIn('id', $activeJobPositionIds)->update(['is_active' => false]);
                $this->info("Deactivated $deactivatedJobs old job positions.");
            }

            // Second pass: Approval Routes
            if (!$dryRun) {
                MstPositionApproval::query()->delete(); // Clear existing to rebuild
            }

            foreach ($employees as $emp) {
                $rawName = strtoupper(trim($emp['nama']));
                if (!isset($jobPositionsCache[$rawName])) continue; // Skipped users

                $secHeadName = $emp['section_head'] ? strtoupper(trim($emp['section_head'])) : null;
                $deptHeadName = $emp['dept_head'] ? strtoupper(trim($emp['dept_head'])) : null;

                // Find the approver job positions
                $secHeadJobs = $secHeadName ? ($jobPositionsCache[$secHeadName] ?? null) : null;
                $deptHeadJobs = $deptHeadName ? ($jobPositionsCache[$deptHeadName] ?? null) : null;

                $userJobs = $jobPositionsCache[$rawName];
                
                foreach ($userJobs as $userJob) {
                    $approvalLevel = 1;
                    
                    // Case Richardus Christian (Finance)
                    // Employee -> Sub Sec Head (Richardus) -> Sec Head (Adhi) -> Dept Head (Martinus)
                    // For Finance Staff:
                    if (str_contains($userJob->position_name, 'Finance Staff')) {
                        // Level 1: Richardus
                        $richardusJobs = $jobPositionsCache['RICHARDUS CHRISTIAN'] ?? null;
                        if ($richardusJobs && !$dryRun) {
                            MstPositionApproval::updateOrCreate(
                                ['position_id' => $userJob->id, 'approval_level' => $approvalLevel++],
                                ['approver_position_id' => $richardusJobs[0]->id]
                            );
                        }
                        
                        // Level 2: Adhi Prasetiyo
                        $adhiJobs = $jobPositionsCache['ADHI PRASETIYO'] ?? null;
                        if ($adhiJobs && !$dryRun) {
                            // Find Adhi's finance job
                            $adhiFin = collect($adhiJobs)->firstWhere('position_name', 'Finance Accounting Sec Head');
                            MstPositionApproval::updateOrCreate(
                                ['position_id' => $userJob->id, 'approval_level' => $approvalLevel++],
                                ['approver_position_id' => $adhiFin->id ?? $adhiJobs[0]->id]
                            );
                        }
                        
                        // Level 3: Martinus
                        $martinusJobs = $jobPositionsCache['MARTINUS CAHYO RAHASTO'] ?? null;
                        if ($martinusJobs && !$dryRun) {
                            MstPositionApproval::updateOrCreate(
                                ['position_id' => $userJob->id, 'approval_level' => $approvalLevel++],
                                ['approver_position_id' => $martinusJobs[0]->id]
                            );
                        }
                        continue;
                    }

                    // Standard pattern A
                    // Match section head based on section
                    if ($secHeadJobs) {
                        $matchedSecHead = collect($secHeadJobs)->first(function($shJob) use ($userJob) {
                            return $shJob->section_id == $userJob->section_id;
                        });
                        $approverJobId = $matchedSecHead ? $matchedSecHead->id : $secHeadJobs[0]->id;
                        
                        if (!$dryRun) {
                            MstPositionApproval::updateOrCreate(
                                ['position_id' => $userJob->id, 'approval_level' => $approvalLevel],
                                ['approver_position_id' => $approverJobId]
                            );
                        }
                        $approvalLevel++;
                    }

                    if ($deptHeadJobs) {
                        $matchedDeptHead = collect($deptHeadJobs)->first(function($dhJob) use ($userJob) {
                            return $dhJob->department_id == $userJob->department_id;
                        });
                        $approverJobId = $matchedDeptHead ? $matchedDeptHead->id : $deptHeadJobs[0]->id;
                        
                        if (!$dryRun) {
                            MstPositionApproval::updateOrCreate(
                                ['position_id' => $userJob->id, 'approval_level' => $approvalLevel],
                                ['approver_position_id' => $approverJobId]
                            );
                        }
                        $approvalLevel++;
                    }
                }
            }

            if ($dryRun) {
                $this->info("Dry run complete. No changes made.");
                DB::rollBack();
            } else {
                $this->info("Migration applied successfully.");
                DB::commit();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during migration: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function normalizeName($name)
    {
        if (!$name) return null;
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }
}
