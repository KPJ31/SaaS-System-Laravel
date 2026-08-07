@extends('layouts.auth')

@section('title', 'About | Elevanix')

@section('content')
<x-public.shell
    title="About Elevanix"
    description="Elevanix is a smart software company management platform for company operations, team work, clients, projects, finance, and reports."
>
    <p>Elevanix helps software companies centralize daily work in one role-aware SaaS workspace. Company Admins manage employees, clients, project requests, projects, tasks, attendance, leave, payments, invoices, documents, reports, notifications, and activity history.</p>
    <p>New company workspaces begin with a registration request. After Super Admin approval, the Company Admin account is activated and the company can begin managing operations securely.</p>
</x-public.shell>
@endsection
