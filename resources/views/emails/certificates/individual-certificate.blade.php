<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #071126;
            background: #ffffff;
        }

        .page {
            position: relative;
            width: 100%;
            height: 100%;
            padding: 28px;
            background: #ffffff;
        }

        .outer-frame {
            position: absolute;
            top: 18px;
            left: 18px;
            right: 18px;
            bottom: 18px;
            border: 3px solid #071126;
        }

        .inner-frame {
            position: absolute;
            top: 28px;
            left: 28px;
            right: 28px;
            bottom: 28px;
            border: 1px solid #0d8fe9;
        }

        .content {
            position: relative;
            z-index: 2;
            padding: 18px 34px 16px;
            text-align: center;
        }

        .brand {
            margin-top: 2px;
            color: #087bc9;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .eyebrow {
            margin-top: 17px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 2.2px;
            color: #64748b;
            text-transform: uppercase;
        }

        .title {
            margin: 5px 0 0;
            font-size: 34px;
            line-height: 1.1;
            font-weight: bold;
            color: #071126;
            text-transform: uppercase;
            letter-spacing: 1.4px;
        }

        .subtitle {
            margin-top: 7px;
            color: #64748b;
            font-size: 12px;
        }

        .divider {
            width: 92px;
            height: 3px;
            margin: 13px auto 13px;
            background: #0d8fe9;
        }

        .presented {
            margin-top: 4px;
            font-size: 12px;
            color: #64748b;
        }

        .learner-name {
            margin: 7px 0 5px;
            font-size: 27px;
            line-height: 1.2;
            font-weight: bold;
            color: #071126;
        }

        .statement {
            width: 82%;
            margin: 0 auto;
            font-size: 12px;
            line-height: 1.55;
            color: #475569;
        }

        .course-box {
            width: 78%;
            margin: 13px auto 0;
            padding: 11px 18px;
            background: #eaf7ff;
            border-left: 4px solid #0d8fe9;
            border-right: 4px solid #0d8fe9;
        }

        .course-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.4px;
        }

        .course-title {
            margin-top: 4px;
            font-size: 18px;
            line-height: 1.25;
            font-weight: bold;
            color: #071126;
        }

        .details {
            width: 82%;
            margin: 14px auto 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .details td {
            width: 25%;
            padding: 8px 5px;
            vertical-align: top;
            border-top: 1px solid #dbe7f0;
            border-bottom: 1px solid #dbe7f0;
        }

        .detail-label {
            display: block;
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 3px;
        }

        .detail-value {
            display: block;
            font-size: 11px;
            font-weight: bold;
            color: #071126;
        }

        .reference {
            margin-top: 12px;
            font-size: 10px;
            color: #64748b;
        }

        .certificate-number {
            font-family: DejaVu Sans Mono, monospace;
            font-weight: bold;
            color: #087bc9;
        }

        .notice {
            width: 82%;
            margin: 11px auto 0;
            font-size: 8.5px;
            line-height: 1.45;
            color: #64748b;
        }

        .footer {
            position: absolute;
            left: 52px;
            right: 52px;
            bottom: 42px;
            border-top: 1px solid #dbe7f0;
            padding-top: 7px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="outer-frame"></div>
    <div class="inner-frame"></div>

    <div class="content">
        <div class="brand">{{ config('app.name', 'SafeSky LMS') }}</div>

        <div class="eyebrow">Official Training Record</div>
        <div class="title">Certificate of Completion</div>
        <div class="subtitle">Individual Training</div>

        <div class="divider"></div>

        <div class="presented">This certificate is proudly presented to</div>

        <div class="learner-name">
            {{ $learnerDisplayName ?: $learner->email }}
        </div>

        <div class="statement">
            This certifies that the learner named above has successfully completed the training course
            and passed the associated final assessment.
        </div>

        <div class="course-box">
            <div class="course-label">Training Course</div>
            <div class="course-title">{{ $course->title ?? 'Training Course' }}</div>
        </div>

        <table class="details" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <span class="detail-label">Final Score</span>
                    <span class="detail-value">{{ number_format((float) ($exam->score ?? 0), 2) }}%</span>
                </td>
                <td>
                    <span class="detail-label">Issue Date</span>
                    <span class="detail-value">{{ $completedAt ?: '-' }}</span>
                </td>
                <td>
                    <span class="detail-label">Issue Time</span>
                    <span class="detail-value">{{ $completedTime ?: '-' }}</span>
                </td>
                <td>
                    <span class="detail-label">Valid Until</span>
                    <span class="detail-value">{{ $validUntil ?: 'No expiration' }}</span>
                </td>
            </tr>
        </table>

        <div class="reference">
            Certificate No.:
            <span class="certificate-number">{{ $certificateNumber }}</span>

            @if($individualRequest?->reference)
                &nbsp;&nbsp; | &nbsp;&nbsp;
                Request: {{ $individualRequest->reference }}
            @endif
        </div>

        <div class="notice">
            This Certificate of Completion confirms successful completion of the training identified above.
            It does not, by itself, constitute a regulatory licence, aircraft type rating or regulatory authorisation.
        </div>
    </div>

    <div class="footer">
        Generated by {{ config('app.name', 'SafeSky LMS') }}.
        This document is associated with enrollment #{{ $enrollment->id }}.
    </div>
</div>
</body>
</html>
