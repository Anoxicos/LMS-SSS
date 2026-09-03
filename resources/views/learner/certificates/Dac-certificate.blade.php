<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Certificate of Completion</title>

    @php
        /*
        |--------------------------------------------------------------------------
        | Fallback assets
        |--------------------------------------------------------------------------
        | Assets passed by the controller remain the priority.
        */
        $assetToDataUri = static function ($path) {
            if (!$path || !is_file($path)) {
                return null;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        };

        if (empty($imageBG)) {
            $imageBG = $assetToDataUri(
                resource_path('images/certificates/dac/background.png')
            );
        }

        if (empty($SECONDEpage)) {
            $SECONDEpage = $assetToDataUri(
                resource_path('images/certificates/dac/second-page.png')
            );
        }

        if (empty($certificateLogo)) {
            $certificateLogo = $assetToDataUri(
                resource_path('images/certificates/dac/logo.png')
            );
        }
    @endphp

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 297mm;
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #102f59;
        }

        /* ================================================================
         | PAGE 1
         | ================================================================ */
        .certificate-page {
            position: relative;
            width: 297mm;
            height: 210mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #fffef9;
            background-image: url("{{ $imageBG }}");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 100% 100%;
            page-break-after: always;
        }

        /* PDF model frames */
        .frame {
            position: absolute;
            pointer-events: none;
            z-index: 2;
        }

        /*
         * DAC frame: exactly 3 rectangular lines.
         * 1) Outer gold line
         * 2) Thin grey-beige line
         * 3) Inner navy line
         * Gold corners are decorative accents, not a fourth frame.
         */
        .frame-gold-outer {
            top: 4.0mm;
            right: 4.0mm;
            bottom: 4.0mm;
            left: 4.0mm;
            border: 0.58mm solid #b18a2d;
        }

        .frame-navy-outer {
            top: 5.75mm;
            right: 5.75mm;
            bottom: 5.75mm;
            left: 5.75mm;
            border: 0.18mm solid #cfc9bc;
        }

        .frame-gold-inner {
            top: 7.15mm;
            right: 7.15mm;
            bottom: 7.15mm;
            left: 7.15mm;
            border: 0.42mm solid #102f59;
        }

        /* The fourth rectangle is intentionally removed. */
        .frame-navy-inner {
            display: none;
        }

        .corner {
            position: absolute;
            width: 13.2mm;
            height: 13.2mm;
            z-index: 3;
        }

        .corner.tl {
            top: 8.55mm;
            left: 8.75mm;
            border-top: 0.45mm solid #b18a2d;
            border-left: 0.45mm solid #b18a2d;
        }

        .corner.tr {
            top: 8.55mm;
            right: 8.75mm;
            border-top: 0.45mm solid #b18a2d;
            border-right: 0.45mm solid #b18a2d;
        }

        .corner.bl {
            bottom: 8.55mm;
            left: 8.75mm;
            border-bottom: 0.45mm solid #b18a2d;
            border-left: 0.45mm solid #b18a2d;
        }

        .corner.br {
            right: 8.75mm;
            bottom: 8.55mm;
            border-right: 0.45mm solid #b18a2d;
            border-bottom: 0.45mm solid #b18a2d;
        }

        /* Header - absolute positioning for reliable vertical centering with Dompdf */
        .cert-header {
            position: absolute;
            top: 9.4mm;
            left: 12.8mm;
            right: 12.8mm;
            height: 18.6mm;
            background: #102f59;
            border-bottom: 0.95mm solid #b18a2d;
            z-index: 4;
            overflow: hidden;
        }

        .header-logo-wrap {
            position: absolute;
            left: 3.8mm;
            top: 2.75mm;
            width: 34mm;
            height: 12mm;
            text-align: center;
            line-height: 12mm;
        }

        .header-logo-wrap img {
            display: inline-block;
            width: auto;
            max-width: 27mm;
            max-height: 12mm;
            margin: 0;
            padding: 0;
            vertical-align: middle;
        }

        .approval-content {
            position: absolute;
            left: 43mm;
            right: 4.8mm;
            top: 3.25mm;
            height: 11.1mm;
            margin: 0;
            padding: 0;
            text-align: right;
        }

        .approval-title {
            margin: 0;
            padding: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 14.9pt;
            line-height: 5.1mm;
            font-weight: bold;
            color: #ffffff;
            white-space: nowrap;
        }

        .approval-ref {
            margin: 1.0mm 0 0;
            padding: 0;
            font-size: 10.8pt;
            line-height: 4.1mm;
            font-weight: bold;
            color: #d4ad45;
            letter-spacing: 0.1px;
            white-space: nowrap;
        }

        /* Title */
        .title-area {
            position: absolute;
            top: 33.2mm;
            left: 18mm;
            right: 18mm;
            text-align: center;
            z-index: 4;
        }

        .certificate-title {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 32pt;
            line-height: 0.94;
            font-weight: bold;
            font-style: italic;
            color: #102f59;
        }

        .completion-title {
            margin-top: 1.2mm;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 15.7pt;
            line-height: 1;
            font-weight: bold;
            color: #b18a2d;
            letter-spacing: 3.55mm;
        }

        .certificate-subtitle {
            margin-top: 1.7mm;
            font-size: 8.2pt;
            line-height: 1;
            font-weight: 500;
            color: #536279;
            letter-spacing: 1.65mm;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .issued-text {
            margin-top: 4.0mm;
            font-size: 13pt;
            line-height: 1.1;
            font-weight: bold;
            color: #102f59;
        }

        /* Identity - same composition as the PDF model */
        .identity-section {
            position: absolute;
            top: 71.6mm;
            left: 17mm;
            right: 17mm;
            z-index: 4;
        }

        .name-table,
        .birth-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .name-table td {
            padding: 0 4.2mm;
            text-align: center;
            vertical-align: bottom;
        }

        .name-table .first-col {
            width: 32%;
        }

        .name-table .middle-col {
            width: 36%;
        }

        .name-table .last-col {
            width: 32%;
        }

        .identity-value {
            min-height: 5.5mm;
            padding: 0 1mm 0.7mm;
            border-bottom: 0.35mm solid #c1a04b;
            color: #102f59;
            font-size: 10.6pt;
            line-height: 4.5mm;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
        }

        .identity-label {
            margin-top: 1.1mm;
            color: #58677a;
            font-size: 7pt;
            line-height: 1;
            font-weight: 500;
            letter-spacing: 1.25mm;
            white-space: nowrap;
        }

        .birth-table {
            width: 84%;
            margin: 8.4mm auto 0;
        }

        .full-name-wrap {
            width: 78%;
            margin: 0 auto;
            text-align: center;
        }

        .full-name-value {
            min-height: 5.4mm;
            padding: 0 1mm 0.65mm;
            border-bottom: 0.35mm solid #c1a04b;
            color: #102f59;
            font-size: 12.2pt;
            line-height: 4.7mm;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
        }

        .full-name-label {
            margin-top: 0.85mm;
            color: #667285;
            font-size: 6.3pt;
            line-height: 1;
            font-weight: 500;
            letter-spacing: 0.95mm;
            white-space: nowrap;
        }

        .birth-table td {
            width: 50%;
            padding: 0 7.2mm;
            text-align: center;
            vertical-align: bottom;
        }

        .birth-value {
            min-height: 4.9mm;
            padding: 0 1mm 0.45mm;
            border-bottom: 0.35mm solid #c1a04b;
            color: #102f59;
            font-size: 9.7pt;
            line-height: 4.2mm;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
        }

        .birth-label {
            margin-top: 0.75mm;
            color: #667285;
            font-size: 6.2pt;
            line-height: 1;
            font-weight: 500;
            letter-spacing: 0.9mm;
            white-space: nowrap;
        }

        /* Training organisation */
        .provider-line {
            position: absolute;
            top: 102.4mm;
            left: 15mm;
            right: 15mm;
            z-index: 4;
            text-align: center;
            white-space: nowrap;
        }

        .provider-prefix {
            margin-right: 1.5mm;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 10.2pt;
            line-height: 1;
            font-style: italic;
            color: #697484;
        }

        .provider-name {
            font-size: 12.7pt;
            line-height: 1;
            font-weight: bold;
            color: #102f59;
        }

        /* Certification statement */
        .certification-text {
            position: absolute;
            top: 110.4mm;
            left: 21mm;
            right: 21mm;
            z-index: 4;
            color: #1d2228;
            text-align: center;
            font-size: 9.4pt;
            line-height: 1.28;
        }

        .certification-text strong {
            font-weight: 700;
        }

        /* Training information */
        .training-box {
            position: absolute;
            top: 125.0mm;
            left: 12mm;
            right: 12mm;
            height: 26.0mm;
            z-index: 4;
            border: 0.34mm solid #b18a2d;
            background: rgba(255, 255, 255, 0.72);
            padding: 1.4mm 3.0mm 1.4mm;
        }

        .training-heading {
            height: 4.3mm;
            border-bottom: 0.2mm solid #d8bf7e;
            color: #b18a2d;
            text-align: center;
            font-size: 8.5pt;
            line-height: 3.1mm;
            font-weight: bold;
            letter-spacing: 1.65mm;
        }

        .training-row {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .training-row td {
            vertical-align: middle;
            text-align: center;
        }

        .training-row.top td {
            padding: 0.8mm 1.4mm 0;
        }

        .training-row.bottom td {
            padding: 0.9mm 1.4mm 0;
        }

        .training-label {
            color: #102f59;
            text-align: center;
            font-size: 7pt;
            line-height: 1.05;
            font-weight: bold;
            text-align: center;
        }

        .auth-caption-title {
            height: 3.8mm;
            line-height: 3.8mm;
            white-space: nowrap;
        }

        .training-value {
            min-height: 4.9mm;
            margin-top: 0.35mm;
            padding: 0 0.2mm 0.3mm;
            border-bottom: 0.28mm solid #c1a04b;
            color: #17263b;
            font-size: 10.1pt;
            line-height: 4.2mm;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
        }

        .training-value.big {
            font-size: 11.4pt;
        }

        .training-value .small-note {
            margin-left: 0.7mm;
            color: #707986;
            font-size: 6.1pt;
            font-weight: normal;
        }

        /* Signature / stamp / QR */
        .auth-section {
            position: absolute;
            top: 159.5mm;
            left: 16mm;
            right: 16mm;
            height: 31.0mm;
            z-index: 4;
        }

        .auth-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .auth-table td {
            width: 33.333%;
            padding: 0 7mm;
            vertical-align: top;
            text-align: center;
        }

        .auth-visual {
            height: 18mm;
            position: relative;
            text-align: center;
            line-height: 18mm;
        }

        .auth-visual img.signature {
            max-height: 17mm;
            max-width: 45mm;
            vertical-align: middle;
        }

        .auth-visual img.stamp {
            max-height: 18mm;
            max-width: 38mm;
            vertical-align: middle;
        }

        .auth-visual img.qr {
            width: 17mm;
            height: 17mm;
            vertical-align: middle;
        }

        .auth-placeholder {
            display: inline-block;
            margin-top: 5.0mm;
            padding: 0.9mm 1.8mm;
            border: 0.18mm dashed #b18a2d;
            color: #6c7784;
            font-size: 5.1pt;
            line-height: 1;
            letter-spacing: 0.8px;
        }

        .auth-caption {
            height: 7.2mm;
            margin-top: 1.0mm;
            padding-top: 1.2mm;
            border-top: 0.3mm solid #b18a2d;
            color: #102f59;
            font-size: 9pt;
            line-height: 1.05;
            font-weight: bold;
            text-align: center;
        }

        .auth-caption-title {
            height: 3.8mm;
            line-height: 3.8mm;
            white-space: nowrap;
        }

        .auth-subcaption {
            margin-top: 0.55mm;
            color: #707986;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 6.6pt;
            line-height: 1;
            font-style: italic;
            font-weight: normal;
        }

        /* Footer */
        .footer-line {
            position: absolute;
            left: 22mm;
            right: 22mm;
            bottom: 9.2mm;
            z-index: 4;
            text-align: center;
            color: #4f5965;
            font-size: 5.1pt;
            line-height: 1.12;
        }

        .footer-line strong {
            color: #102f59;
            font-size: 5.6pt;
        }

        /* ================================================================
         | PAGE 2
         | ================================================================ */
        .second-page {
            position: relative;
            width: 297mm;
            height: 210mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
            page-break-inside: avoid;
            background: #ffffff;
        }

        .second-page-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 297mm;
            height: 210mm;
            display: block;
            margin: 0;
            padding: 0;
            border: 0;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }
    </style>
</head>
<body>
@php
    /* ----------------------------------------------------------------------
     | Learner data
     | ---------------------------------------------------------------------- */
    $firstName = trim((string) ($learner->first_name ?? ''));
    $middleName = trim((string) ($learner->middle_name ?? ''));
    $lastName = trim((string) ($learner->last_name ?? ''));

    $birthDate = $learner->formatted_date_of_birth
        ?? $learner->date_of_birth
        ?? '';

    $birthPlace = trim((string) ($learner->place_of_birth ?? ''));

    $fullName = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
        $firstName,
        $middleName,
        $lastName,
    ]))));

    $hasMiddleName = $middleName !== '';

    /* ----------------------------------------------------------------------
     | Training data
     | ---------------------------------------------------------------------- */
    $courseTitle = trim((string) ($course->title ?? ''));
    $courseReference = trim((string) ($course->ref ?? ''));
    $courseLevel = trim((string) ($course->skill_level ?? ''));
    $courseMethod = trim((string) ($course->method ?? 'e-Learning (LMS)'));

    $durationHours = $course->duration_hours ?? null;
    $durationText = '';

    if ($durationHours !== null && $durationHours !== '') {
        $durationNumber = (float) $durationHours;
        $durationText = rtrim(
            rtrim(number_format($durationNumber, 2, '.', ''), '0'),
            '.'
        ) . ' h';
    }

    /* ----------------------------------------------------------------------
     | Score
     | ---------------------------------------------------------------------- */
    $scoreValue = $exam->score ?? null;
    $scoreText = '';

    if ($scoreValue !== null && $scoreValue !== '') {
        $scoreText = rtrim(
            rtrim(number_format((float) $scoreValue, 2, '.', ''), '0'),
            '.'
        ) . ' %';
    }

    $passingScore = $exam->passing_score ?? 75;

    /* ----------------------------------------------------------------------
     | Subtitle and main statement
     | ---------------------------------------------------------------------- */
    $certificateTrainingLabel = $certificateTrainingLabel ?? $courseTitle;

    $defaultStatement = 'has successfully completed the training <strong>'
        . e($courseTitle)
        . '</strong> and its final assessment, in accordance with the programme approved by the '
        . '<strong>Directorate General of Civil Aviation (DGAC)</strong> of the Kingdom of Morocco.';

    $certificateStatement = $certificateStatement ?? $defaultStatement;

    /* Optional assets */
    $signatureImage = $signatureImage ?? null;
    $stampImage = $stampImage ?? null;
    $qrImage = $qrImage ?? null;
@endphp

<div class="certificate-page">
    <div class="frame frame-gold-outer"></div>
    <div class="frame frame-navy-outer"></div>
    <div class="frame frame-gold-inner"></div>
    <div class="frame frame-navy-inner"></div>

    <div class="corner tl"></div>
    <div class="corner tr"></div>
    <div class="corner bl"></div>
    <div class="corner br"></div>

    <div class="cert-header">
        <div class="header-logo-wrap">
            @if(!empty($certificateLogo))
                <img src="{{ $certificateLogo }}" alt="HALIAIR">
            @endif
        </div>

        <div class="approval-content">
            <div class="approval-title">
                Training Centre approved by the DGAC — Kingdom of Morocco
            </div>
            <div class="approval-ref">
                DGAC Morocco · Approval No.: CN-FS27/2014
            </div>
        </div>
    </div>

    <div class="title-area">
        <div class="certificate-title">Certificate</div>
        <div class="completion-title">OF COMPLETION</div>
        <div class="certificate-subtitle">
            CERTIFICATE OF COMPLETION
        </div>
        <div class="issued-text">
            This Certificate of Completion is issued to:
        </div>
    </div>

    <div class="identity-section">
        @if($hasMiddleName)
            <table class="name-table">
                <tr>
                    <td class="first-col">
                        <div class="identity-value">{{ $firstName ?: ' ' }}</div>
                        <div class="identity-label">FIRST NAME</div>
                    </td>

                    <td class="middle-col">
                        <div class="identity-value">{{ $middleName }}</div>
                        <div class="identity-label">MIDDLE NAME (if applicable)</div>
                    </td>

                    <td class="last-col">
                        <div class="identity-value">{{ $lastName ?: ' ' }}</div>
                        <div class="identity-label">SURNAME</div>
                    </td>
                </tr>
            </table>
        @else
            <div class="full-name-wrap">
                <div class="full-name-value">{{ $fullName ?: ' ' }}</div>
                <div class="full-name-label">FULL NAME</div>
            </div>
        @endif

        <table class="birth-table">
            <tr>
                <td>
                    <div class="birth-value">{{ $birthDate ?: ' ' }}</div>
                    <div class="birth-label">DATE OF BIRTH</div>
                </td>
                <td>
                    <div class="birth-value">{{ $birthPlace ?: ' ' }}</div>
                    <div class="birth-label">PLACE OF BIRTH</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="provider-line">
        <span class="provider-prefix">Issued by</span>
        <span class="provider-name">HALIAIR Private Training Center</span>
    </div>

    <div class="certification-text">
        {!! $certificateStatement !!}
    </div>

    <div class="training-box">
        <div class="training-heading">TRAINING INFORMATION</div>

        <table class="training-row top">
            <tr>
                <td style="width: 56%;">
                    <div class="training-label">Course Title</div>
                    <div class="training-value big">{{ $courseTitle ?: ' ' }}</div>
                </td>
                <td style="width: 22%;">
                    <div class="training-label">Course Reference</div>
                    <div class="training-value">{{ $courseReference ?: ' ' }}</div>
                </td>
                <td style="width: 22%;">
                    <div class="training-label">Training Level</div>
                    <div class="training-value">{{ $courseLevel ?: ' ' }}</div>
                </td>
            </tr>
        </table>

        <table class="training-row bottom">
            <tr>
                <td style="width: 20%;">
                    <div class="training-label">Training Method</div>
                    <div class="training-value">{{ $courseMethod ?: ' ' }}</div>
                </td>
                <td style="width: 18%;">
                    <div class="training-label">Duration</div>
                    <div class="training-value">{{ $durationText ?: ' ' }}</div>
                </td>
                <td style="width: 24%;">
                    <div class="training-label">Certificate No.</div>
                    <div class="training-value">{{ $certificateNumber ?: ' ' }}</div>
                </td>
                <td style="width: 19%;">
                    <div class="training-label">Final Score</div>
                    <div class="training-value">
                        {{ $scoreText ?: ' ' }}
                        <span class="small-note">(min. {{ $passingScore }} %)</span>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="training-label">Date of Issue</div>
                    <div class="training-value">{{ $completedAt ?: ' ' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="auth-section">
        <table class="auth-table">
            <tr>
                <td>
                    <div class="auth-visual">
                        @if($signatureImage)
                            <img src="{{ $signatureImage }}" alt="Signature" class="signature">
                        @endif
                    </div>
                    <div class="auth-caption">
                        <div class="auth-caption-title">Head of Training</div>
                        <div class="auth-subcaption">Signature</div>
                    </div>
                </td>

                <td>
                    <div class="auth-visual">
                        @if($stampImage)
                            <img src="{{ $stampImage }}" alt="Official stamp" class="stamp">
                        @else
                            <span class="auth-placeholder">STAMP</span>
                        @endif
                    </div>
                    <div class="auth-caption">
                        <div class="auth-caption-title">Official Stamp</div>
                    </div>
                </td>

                <td>
                    <div class="auth-visual">
                        @if($qrImage)
                            <img src="{{ $qrImage }}" alt="QR verification" class="qr">
                        @else
                            <span class="auth-placeholder">QR</span>
                        @endif
                    </div>
                    <div class="auth-caption">
                        <div class="auth-caption-title">Verification</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-line">
        <strong>
            HALIAIR Private Training Center · Casablanca, Kingdom of Morocco · DGAC Approval No. CN-FS27/2014
        </strong>
        <br>
        This certificate does not constitute an aircraft maintenance licence, an aircraft type rating, or any regulatory authorisation.
    </div>
</div>

@if(!empty($SECONDEpage))
    <div class="second-page">
        <img
            src="{{ $SECONDEpage }}"
            alt="Training syllabus"
            class="second-page-image"
        >
    </div>
@endif
</body>
</html>
