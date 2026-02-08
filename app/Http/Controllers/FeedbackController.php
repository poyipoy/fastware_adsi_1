<?php

namespace App\Http\Controllers;

use App\Models\MstQst;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Display the feedback form
     */
    public function index()
    {
        return view('feedback.form');
    }

    /**
     * Handle feedback form submission
     */
    public function submit(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'jabatan' => 'nullable|string|max:255',
            'system_name' => 'required|string|max:255',
            'core_metrics' => 'required|array',
            'features' => 'required|array|min:1',
            'obstacles' => 'nullable|string',
            'suggestions' => 'nullable|string',
        ], [
            'system_name.required' => 'Nama Sistem/Modul wajib diisi',
            'features.min' => 'Minimal harus ada satu fitur yang dinilai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create new feedback entry
            $feedback = MstQst::create([
                'user_name' => Auth::user()->name,
                'jabatan' => $request->jabatan,
                'system_name' => $request->system_name,
                'core_metrics' => $request->core_metrics,
                'features' => $request->features,
                'obstacles' => $request->obstacles,
                'suggestions' => $request->suggestions,
                'is_active' => 1,
                'status' => 'submitted',
                'modified_at' => Auth::user()->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback berhasil dikirim. Terima kasih atas partisipasi Anda!',
                'data' => $feedback
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display list of all feedback submissions
     */
    public function list(Request $request)
    {
        $query = MstQst::orderBy('created_at', 'desc');

        // Filter by system name if provided
        if ($request->has('system_name') && $request->system_name != '') {
            $query->where('system_name', 'like', '%' . $request->system_name . '%');
        }

        // Filter by user name if provided
        if ($request->has('user_name') && $request->user_name != '') {
            $query->where('user_name', 'like', '%' . $request->user_name . '%');
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Paginate results
        $feedbacks = $query->paginate(15);

        return view('feedback.list', compact('feedbacks'));
    }

    /**
     * Display detailed view of a specific feedback
     */
    public function detail($id)
    {
        $feedback = MstQst::findOrFail($id);
        return view('feedback.detail', compact('feedback'));
    }

    /**
     * Display feedback dashboard with charts and statistics
     */
    public function dashboard()
    {
        // Get all feedback
        $allFeedback = MstQst::all();
        
        // Calculate statistics
        $totalFeedback = $allFeedback->count();
        $avgOverallRating = 0;
        $totalSystems = $allFeedback->unique('system_name')->count();
        
        // Calculate average rating
        if ($totalFeedback > 0) {
            $totalRating = 0;
            $ratingCount = 0;
            
            foreach ($allFeedback as $feedback) {
                $coreMetrics = is_array($feedback->core_metrics) ? $feedback->core_metrics : json_decode($feedback->core_metrics, true);
                if ($coreMetrics) {
                    foreach ($coreMetrics as $value) {
                        if (is_numeric($value) && $value > 0) {
                            $totalRating += $value;
                            $ratingCount++;
                        }
                    }
                }
            }
            
            $avgOverallRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 2) : 0;
        }
        
        // Get feedback by system with average ratings
        $systemRatings = [];
        foreach ($allFeedback->groupBy('system_name') as $systemName => $feedbacks) {
            $totalRating = 0;
            $count = 0;
            
            foreach ($feedbacks as $feedback) {
                $coreMetrics = is_array($feedback->core_metrics) ? $feedback->core_metrics : json_decode($feedback->core_metrics, true);
                if ($coreMetrics) {
                    foreach ($coreMetrics as $value) {
                        if (is_numeric($value) && $value > 0) {
                            $totalRating += $value;
                            $count++;
                        }
                    }
                }
            }
            
            $systemRatings[] = [
                'name' => $systemName,
                'rating' => $count > 0 ? round($totalRating / $count, 2) : 0,
                'count' => $feedbacks->count()
            ];
        }
        
        // Sort by rating
        usort($systemRatings, function($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });
        
        // Get feedback trend by month
        $feedbackTrend = MstQst::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();
        
        // Get core metrics breakdown
        $metricsBreakdown = [
            'akurasi' => 0,
            'responsivitas' => 0,
            'stabilitas' => 0,
            'efisiensi' => 0
        ];
        
        $metricsCount = 0;
        foreach ($allFeedback as $feedback) {
            $coreMetrics = is_array($feedback->core_metrics) ? $feedback->core_metrics : json_decode($feedback->core_metrics, true);
            if ($coreMetrics) {
                foreach ($metricsBreakdown as $key => $value) {
                    if (isset($coreMetrics[$key]) && is_numeric($coreMetrics[$key])) {
                        $metricsBreakdown[$key] += $coreMetrics[$key];
                    }
                }
                $metricsCount++;
            }
        }
        
        // Calculate averages
        foreach ($metricsBreakdown as $key => $value) {
            $metricsBreakdown[$key] = $metricsCount > 0 ? round($value / $metricsCount, 2) : 0;
        }
        
        return view('feedback.dashboard', compact(
            'totalFeedback',
            'avgOverallRating',
            'totalSystems',
            'systemRatings',
            'feedbackTrend',
            'metricsBreakdown'
        ));
    }
}
