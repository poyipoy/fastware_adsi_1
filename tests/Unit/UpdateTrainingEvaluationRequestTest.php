<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateTrainingEvaluationRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateTrainingEvaluationRequestTest extends TestCase
{
    public function test_valid_group_evaluation_payload_is_accepted(): void
    {
        $request = $this->requestFor($this->validPayload());

        $request->validateResolved();

        $this->assertSame('Sharing Knowledge', $request->validated('metode_evaluasi'));
    }

    public function test_unknown_option_is_rejected(): void
    {
        $request = $this->requestFor(array_merge($this->validPayload(), [
            'efektif' => 'Mungkin',
        ]));

        try {
            $request->validateResolved();
            $this->fail('Nilai di luar whitelist seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('efektif', $exception->errors());
        }
    }

    private function validPayload(): array
    {
        return [
            'relevansi' => 'Ya',
            'alasan_relevansi' => 'Sesuai kebutuhan',
            'rekomendasi' => 'Lanjutkan',
            'alasan_rekomendasi' => null,
            'kelengkapan_materi' => 'Lengkap',
            'lokasi' => 'Dekat',
            'metode_pengajaran' => 'Mudah Dimengerti',
            'fasilitas' => 'Lengkap',
            'lainnya_1' => null,
            'metode_evaluasi' => 'Sharing Knowledge',
            'minat' => 'Tinggi',
            'daya_serap' => 'Menguasai Materi',
            'penerapan' => 'Cepat',
            'lainnya_2' => null,
            'efektif' => 'Efektif',
            'catatan_tambahan' => 'Lanjutkan secara berkala',
        ];
    }

    private function requestFor(array $payload): UpdateTrainingEvaluationRequest
    {
        $request = UpdateTrainingEvaluationRequest::create(
            '/update-evaluasi/952',
            'PUT',
            $payload,
        );
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        return $request;
    }
}
