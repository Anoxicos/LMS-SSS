<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        New Individual Training Request
    </title>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f4fbff;
        font-family:Arial,Helvetica,sans-serif;
        color:#071126;
    "
>

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        background:#f4fbff;
        padding:40px 15px;
    "
>
    <tr>
        <td align="center">

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                border="0"
                style="
                    max-width:680px;
                    background:#ffffff;
                    border:1px solid #e4edf4;
                    border-radius:16px;
                    overflow:hidden;
                "
            >

                <!-- Header -->

                <tr>
                    <td
                        style="
                            background:#071126;
                            padding:32px 35px;
                            text-align:center;
                        "
                    >

                        <div
                            style="
                                color:#37cced;
                                font-size:13px;
                                font-weight:bold;
                                letter-spacing:1.5px;
                                text-transform:uppercase;
                                margin-bottom:9px;
                            "
                        >
                            SafeSky LMS
                        </div>

                        <h1
                            style="
                                margin:0;
                                color:#ffffff;
                                font-size:25px;
                            "
                        >
                            New Individual Training Request
                        </h1>

                    </td>
                </tr>


                <!-- Content -->

                <tr>
                    <td style="padding:35px;">

                        <p
                            style="
                                margin-top:0;
                                color:#64748b;
                                font-size:15px;
                                line-height:1.7;
                            "
                        >
                            A learner has submitted a new
                            individual training request and
                            it is waiting for review.
                        </p>


                        <!-- Reference -->

                        <div
                            style="
                                margin:25px 0;
                                padding:20px;
                                background:#eaf7ff;
                                border-left:4px solid #0d8fe9;
                                border-radius:8px;
                            "
                        >

                            <div
                                style="
                                    color:#64748b;
                                    font-size:11px;
                                    text-transform:uppercase;
                                    letter-spacing:1px;
                                    margin-bottom:6px;
                                "
                            >
                                Request Reference
                            </div>

                            <div
                                style="
                                    color:#071126;
                                    font-size:20px;
                                    font-weight:bold;
                                "
                            >
                                {{ $individualRequest->reference }}
                            </div>

                        </div>


                        <!-- Learner Information -->

                        <h3
                            style="
                                margin:30px 0 12px;
                                font-size:16px;
                                color:#071126;
                            "
                        >
                            Learner Information
                        </h3>

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            style="
                                border-collapse:collapse;
                            "
                        >

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                        width:35%;
                                    "
                                >
                                    Learner
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        font-weight:bold;
                                    "
                                >
                                    {{
                                        $learner->full_name
                                        ?: collect([
                                            $learner->first_name,
                                            $learner->middle_name,
                                            $learner->last_name,
                                        ])->filter()->implode(' ')
                                    }}
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                    "
                                >
                                    Email
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                    "
                                >
                                    {{ $learner->email }}
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                        color:#64748b;
                                    "
                                >
                                    Country
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                        border-bottom:1px solid #e4edf4;
                                    "
                                >
                                    {{ $learner->country ?: '-' }}
                                </td>
                            </tr>

                            <tr>
                                <td
                                    style="
                                        padding:12px 0;
                                        color:#64748b;
                                    "
                                >
                                    Submitted At
                                </td>

                                <td
                                    style="
                                        padding:12px 0;
                                    "
                                >
                                    {{
                                        $individualRequest->submitted_at
                                            ? \Illuminate\Support\Carbon::parse(
                                                $individualRequest->submitted_at
                                            )->format('d/m/Y H:i')
                                            : '-'
                                    }}
                                </td>
                            </tr>

                        </table>


                        <!-- Courses -->

                        <h3
                            style="
                                margin:30px 0 12px;
                                font-size:16px;
                                color:#071126;
                            "
                        >
                            Requested Courses
                            ({{ $individualRequest->items->count() }})
                        </h3>


                        @foreach($individualRequest->items as $item)

                            <div
                                style="
                                    margin-bottom:10px;
                                    padding:15px 17px;
                                    border:1px solid #e4edf4;
                                    border-radius:8px;
                                    background:#ffffff;
                                "
                            >

                                <div
                                    style="
                                        color:#071126;
                                        font-size:14px;
                                        font-weight:bold;
                                    "
                                >
                                    {{
                                        $item->course?->title
                                        ?? 'Course #' . $item->course_id
                                    }}
                                </div>

                                <div
                                    style="
                                        margin-top:6px;
                                        color:#64748b;
                                        font-size:12px;
                                    "
                                >
                                    {{
                                        number_format(
                                            (float) $item->price_snapshot,
                                            2
                                        )
                                    }}

                                    {{ $item->currency_snapshot }}
                                </div>

                            </div>

                        @endforeach


                        <!-- Note -->

                        @if(filled($individualRequest->learner_note))

                            <h3
                                style="
                                    margin:30px 0 12px;
                                    font-size:16px;
                                "
                            >
                                Learner Note
                            </h3>

                            <div
                                style="
                                    padding:17px;
                                    background:#f8fafc;
                                    border-radius:8px;
                                    color:#64748b;
                                    font-size:14px;
                                    line-height:1.7;
                                "
                            >
                                {{
                                    $individualRequest->learner_note
                                }}
                            </div>

                        @endif


                        <!-- Pending -->

                        <div
                            style="
                                margin-top:25px;
                                padding:16px 18px;
                                background:#fff8e6;
                                border-left:4px solid #f7b84b;
                                border-radius:8px;
                                color:#8a6116;
                                font-size:13px;
                                line-height:1.6;
                            "
                        >
                            This request is currently
                            <strong>Pending</strong>
                            and requires administrator review.
                        </div>


                        <!-- CTA -->

                        <table
                            align="center"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="margin-top:30px;"
                        >
                            <tr>
                                <td
                                    bgcolor="#071126"
                                    style="border-radius:8px;"
                                >

                                    <a
                                        href="{{ $adminUrl }}"
                                        style="
                                            display:inline-block;
                                            padding:14px 30px;
                                            color:#ffffff;
                                            text-decoration:none;
                                            font-size:14px;
                                            font-weight:bold;
                                        "
                                    >
                                        Open SafeSky LMS
                                    </a>

                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>


                <!-- Footer -->

                <tr>
                    <td
                        style="
                            background:#071126;
                            padding:20px;
                            text-align:center;
                            color:#94a3b8;
                            font-size:12px;
                        "
                    >
                        &copy;
                        {{ date('Y') }}
                        SafeSky Solution.
                        Learning Management System.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>