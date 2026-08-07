@extends('layouts.auth')

@section('title', 'Privacy Policy | Elevanix')

@section('content')
<x-public.shell
    title="Privacy Policy"
    description="This page summarizes the privacy expectations for the Elevanix project workspace."
>
    <p>Elevanix stores company, user, project, work, finance, notification, and audit information required to operate the platform. Access is controlled by authenticated roles and company ownership rules.</p>
    <p>Public company registration data is used to review workspace requests. Passwords are hashed and are not sent by email.</p>
</x-public.shell>
@endsection
