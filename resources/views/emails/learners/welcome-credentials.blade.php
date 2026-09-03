<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Welcome to SafeSky LMS
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
                    max-width:650px;
                    background:#ffffff;
                    border:1px solid #e4edf4;
                    border-radius:16px;
                    overflow:hidden;
                "
            >

                <!-- =====================================================
                     Header
                ====================================================== -->

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
                                margin-bottom:10px;
                            "
                        >
                            SafeSky LMS
                        </div>

                        <h1
                            style="
                                margin:0;
                                color:#ffffff;
                                font-size:26px;
                                line-height:1.3;
                            "
                        >
                            Welcome to SafeSky LMS
                        </h1>

                    </td>

                </tr>

                <!-- =====================================================
                     Content
                ====================================================== -->

                <tr>

                    <td
                        style="
                            padding:36px;
                        "
                    >

                        <p
                            style="
                                margin-top:0;
                                color:#64748b;
                                font-size:15px;
                                line-height:1.7;
                            "
                        >
                            Hello

                            <strong
                                style="
                                    color:#071126;
                                "
                            >
                                {{
                                    $learner->full_name
                                    ?: $learner->email
                                }}
                            </strong>,
                        </p>

                        <p
                            style="
                                color:#64748b;
                                font-size:15px;
                                line-height:1.7;
                            "
                        >
                            Your learner account has been
                            successfully created on the
                            SafeSky Learning Management
                            System.
                        </p>

                        @if($learner->company)

                            <p
                                style="
                                    color:#64748b;
                                    font-size:15px;
                                    line-height:1.7;
                                "
                            >
                                You have been registered under

                                <strong
                                    style="
                                        color:#071126;
                                    "
                                >
                                    {{
                                        $learner
                                            ->company
                                            ->name
                                    }}
                                </strong>.
                            </p>

                        @endif

                        <!-- =================================================
                             Credentials
                        ================================================== -->

                        <div
                            style="
                                margin:28px 0;
                                padding:24px;
                                background:#eaf7ff;
                                border-left:4px solid #0d8fe9;
                                border-radius:10px;
                            "
                        >

                            <div
                                style="
                                    color:#087bc9;
                                    font-size:12px;
                                    font-weight:bold;
                                    text-transform:uppercase;
                                    letter-spacing:1px;
                                    margin-bottom:18px;
                                "
                            >
                                Your Login Information
                            </div>

                            <!-- Login -->

                            <div
                                style="
                                    margin-bottom:18px;
                                "
                            >

                                <div
                                    style="
                                        color:#64748b;
                                        font-size:12px;
                                        margin-bottom:5px;
                                    "
                                >
                                    Login / Email
                                </div>

                                <div
                                    style="
                                        color:#071126;
                                        font-size:16px;
                                        font-weight:bold;
                                        word-break:break-all;
                                    "
                                >
                                    {{ $learner->email }}
                                </div>

                            </div>

                            <!-- Password -->

                            <div>

                                <div
                                    style="
                                        color:#64748b;
                                        font-size:12px;
                                        margin-bottom:5px;
                                    "
                                >
                                    Temporary Password
                                </div>

                                <div
                                    style="
                                        color:#071126;
                                        font-size:18px;
                                        font-weight:bold;
                                        letter-spacing:1px;
                                    "
                                >
                                    {{ $temporaryPassword }}
                                </div>

                            </div>

                        </div>

                        <!-- =================================================
                             Security Notice
                        ================================================== -->

                        <div
                            style="
                                margin:25px 0;
                                padding:18px 20px;
                                background:#fff8e6;
                                border-left:4px solid #f7b84b;
                                border-radius:8px;
                            "
                        >

                            <div
                                style="
                                    color:#8a6116;
                                    font-size:14px;
                                    font-weight:bold;
                                    margin-bottom:7px;
                                "
                            >
                                Important Security Notice
                            </div>

                            <div
                                style="
                                    color:#8a6116;
                                    font-size:13px;
                                    line-height:1.7;
                                "
                            >
                                For security reasons, we strongly
                                recommend that you change your
                                password after your first login.

                                Do not share your login credentials
                                with anyone.
                            </div>

                        </div>

                        <!-- =================================================
                             Login button
                        ================================================== -->

                        <table
                            align="center"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="
                                margin-top:30px;
                            "
                        >

                            <tr>

                                <td
                                    bgcolor="#071126"
                                    style="
                                        border-radius:8px;
                                    "
                                >

                                    <a
                                        href="{{ $loginUrl }}"
                                        style="
                                            display:inline-block;
                                            padding:15px 32px;
                                            color:#ffffff;
                                            text-decoration:none;
                                            font-size:14px;
                                            font-weight:bold;
                                        "
                                    >
                                        Sign In to SafeSky LMS
                                    </a>

                                </td>

                            </tr>

                        </table>

                        <!-- URL fallback -->

                        <div
                            style="
                                margin-top:28px;
                                padding-top:20px;
                                border-top:1px solid #e4edf4;
                            "
                        >

                            <p
                                style="
                                    margin:0 0 6px;
                                    color:#94a3b8;
                                    font-size:12px;
                                    line-height:1.6;
                                "
                            >
                                If the button does not work,
                                copy and paste this link into
                                your browser:
                            </p>

                            <p
                                style="
                                    margin:0;
                                    font-size:12px;
                                    line-height:1.6;
                                    word-break:break-all;
                                "
                            >

                                <a
                                    href="{{ $loginUrl }}"
                                    style="
                                        color:#0d8fe9;
                                    "
                                >
                                    {{ $loginUrl }}
                                </a>

                            </p>

                        </div>

                    </td>

                </tr>

                <!-- =====================================================
                     Footer
                ====================================================== -->

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