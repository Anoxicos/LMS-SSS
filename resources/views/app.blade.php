<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-layout="vertical"
    data-topbar="light"
    data-sidebar="dark"
    data-sidebar-size="lg"
    data-sidebar-image="none"
    data-bs-theme="light"
    data-body-image="none"
    data-preloader="disable"
    data-layout-width="fluid"
    data-layout-position="fixed"
    data-layout-style="default"
>

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title inertia>
        {{ config('app.name', 'E-learning') }}
    </title>

    <meta
        name="description"
        content="E-learning platform for managing online courses, learners, companies, training content, examinations and certifications."
    >

    <meta
        name="keywords"
        content="E-learning, LMS, online courses, training platform, learners, examinations, certificates, online education"
    >

    <meta
        name="author"
        content="{{ config('app.name', 'E-learning') }}"
    >

    <meta
        name="application-name"
        content="{{ config('app.name', 'E-learning') }}"
    >

    <meta
        name="theme-color"
        content="#405189"
    >

    <!-- Open Graph -->
    <meta
        property="og:type"
        content="website"
    >

    <meta
        property="og:title"
        content="{{ config('app.name', 'E-learning') }}"
    >

    <meta
        property="og:description"
        content="Access online training courses, track learner progress, manage examinations and generate certificates."
    >

    <meta
        property="og:image"
        content="{{ URL::asset('image/logo-social.png') }}"
    >

    <meta
        property="og:url"
        content="{{ url()->current() }}"
    >

    <meta
        property="og:site_name"
        content="{{ config('app.name', 'E-learning') }}"
    >

    <!-- Twitter -->
    <meta
        name="twitter:card"
        content="summary_large_image"
    >

    <meta
        name="twitter:title"
        content="{{ config('app.name', 'E-learning') }}"
    >

    <meta
        name="twitter:description"
        content="Online learning and training management platform."
    >

    <meta
        name="twitter:image"
        content="{{ URL::asset('image/logo-social.png') }}"
    >

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link
        rel="shortcut icon"
        href="{{ URL::asset('image/favicon.ico') }}"
        type="image/x-icon"
    >

    <link
        rel="icon"
        href="{{ URL::asset('image/favicon.ico') }}"
        type="image/x-icon"
    >

    <!-- Inertia routes -->
    @routes

    <!-- Application assets -->
    @vite([
        'resources/js/app.js',
        "resources/js/Pages/{$page['component']}.vue"
    ])

    <!-- Inertia page metadata -->
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>