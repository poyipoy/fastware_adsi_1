<?php

namespace App\Http\Controllers\KnowledgeManagement;

use App\Http\Controllers\Controller;
use App\Services\KnowledgeManagement\KmGamificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KmRecognitionController extends Controller
{
    public function certificate(Request $request, KmGamificationService $gamification): Response
    {
        $profile = $gamification->profile($request->user());
        abort_if($profile['tier'] === null, 422, 'Sertifikat tersedia mulai tier Bronze.');
        $bytes = Pdf::loadView('knowlege_management.recognition.certificate', [
            'user' => $request->user(),
            'profile' => $profile,
            'issuedAt' => now(),
        ])->setPaper('a4', 'landscape')->output();

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="km-recognition-'.$request->user()->getKey().'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
