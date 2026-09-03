<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reset Your Password
    </title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f4fbff;
        font-family: Arial, Helvetica, sans-serif;
        color: #071126;
    "
>

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width: 100%;
        background-color: #f4fbff;
        padding: 40px 15px;
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
                    width: 100%;
                    max-width: 600px;
                    background-color: #ffffff;
                    border-radius: 16px;
                    overflow: hidden;
                    border: 1px solid #e4edf4;
                "
            >

                <!-- Top line -->
                <tr>
                    <td
                        style="
                            height: 7px;
                            background-color: #0d8fe9;
                        "
                    ></td>
                </tr>

                <!-- Content -->
                <tr>
                    <td
                        style="
                            padding: 45px 40px;
                            text-align: center;
                        "
                    >

                        <div
                            style="
                                width: 70px;
                                height: 70px;
                                line-height: 70px;
                                margin: 0 auto 25px;
                                background-color: #eaf7ff;
                                border-radius: 50%;
                                font-size: 30px;
                            "
                        >
                            🔐
                        </div>

                        <h1
                            style="
                                margin: 0 0 20px;
                                color: #071126;
                                font-size: 26px;
                            "
                        >
                            Reset Your Password
                        </h1>

                        <p
                            style="
                                color: #64748b;
                                font-size: 15px;
                                line-height: 1.7;
                                margin-bottom: 12px;
                            "
                        >
                            Hello
                            <strong>
                                {{ $user->full_name }}
                            </strong>,
                        </p>

                        <p
                            style="
                                color: #64748b;
                                font-size: 15px;
                                line-height: 1.7;
                                margin-bottom: 30px;
                            "
                        >
                            We received a request to reset
                            the password for your SafeSky LMS
                            account.
                        </p>

                        <!-- Button -->
                        <table
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            align="center"
                        >
                            <tr>
                                <td
                                    align="center"
                                    bgcolor="#071126"
                                    style="
                                        border-radius: 8px;
                                    "
                                >

                                    <a
                                        href="{{ $resetUrl }}"
                                        style="
                                            display: inline-block;
                                            padding: 15px 30px;
                                            color: #ffffff;
                                            text-decoration: none;
                                            font-size: 15px;
                                            font-weight: bold;
                                        "
                                    >
                                        Reset Password
                                    </a>

                                </td>
                            </tr>
                        </table>

                        <p
                            style="
                                color: #64748b;
                                font-size: 14px;
                                line-height: 1.7;
                                margin-top: 30px;
                            "
                        >
                            This password reset link will
                            expire in

                            <strong>
                                {{ $expirationMinutes }} minutes.
                            </strong>
                        </p>

                        <p
                            style="
                                color: #94a3b8;
                                font-size: 13px;
                                line-height: 1.7;
                                margin-top: 25px;
                            "
                        >
                            If you did not request a password
                            reset, no further action is required.
                        </p>

                        <hr
                            style="
                                border: 0;
                                border-top: 1px solid #e4edf4;
                                margin: 30px 0;
                            "
                        >

                        <p
                            style="
                                color: #94a3b8;
                                font-size: 12px;
                                line-height: 1.6;
                                word-break: break-all;
                            "
                        >
                            If the button does not work,
                            copy and paste this link into
                            your browser:
                            <br><br>

                            <a
                                href="{{ $resetUrl }}"
                                style="
                                    color: #0d8fe9;
                                "
                            >
                                {{ $resetUrl }}
                            </a>
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td
                        style="
                            background-color: #071126;
                            padding: 20px;
                            text-align: center;
                            color: #ffffff;
                            font-size: 12px;
                        "
                    >
                        © {{ date('Y') }}
                        SafeSky Solution.
                        All rights reserved.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>