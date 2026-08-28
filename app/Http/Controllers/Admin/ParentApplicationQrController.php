<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ParentApplicationQrController extends Controller
{
    public function show(): View
    {
        return view('admin.parents.application-qr', [
            'applicationUrl' => route('parent.application.create'),
        ]);
    }

    public function image(): Response
    {
        return $this->qrResponse();
    }

    public function download(): Response
    {
        return $this->qrResponse(true);
    }

    private function qrResponse(bool $download = false): Response
    {
        $result = (new Builder(
            writer: new SvgWriter,
            writerOptions: [
                SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
            ],
            data: route('parent.application.create'),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 420,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(70, 31, 20),
            backgroundColor: new Color(255, 255, 255),
        ))->build();

        $response = response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'no-store, max-age=0',
        ]);

        if ($download) {
            $response->header('Content-Disposition', 'attachment; filename="amoracare-parent-application-qr.svg"');
        }

        return $response;
    }
}
