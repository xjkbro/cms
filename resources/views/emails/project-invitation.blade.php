<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Invitation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 30px;
        }
        .project-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .project-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #3b82f6;
            color: white;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }
        .buttons {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 0 10px;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .expires {
            background: #fef3c7;
            color: #92400e;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <div class="title">You've been invited to collaborate!</div>
        </div>

        <div class="content">
            <p>Hello!</p>

            <p><strong>{{ $invitedBy->name }}</strong> has invited you to collaborate on their project.</p>

            <div class="project-info">
                <div class="project-name">{{ $project->name }}</div>
                @if($project->description)
                    <p style="margin: 10px 0; color: #6b7280;">{{ $project->description }}</p>
                @endif
                <div style="margin-top: 15px;">
                    <strong>Your role:</strong>
                    <span class="role-badge">{{ $invitation->role }}</span>
                </div>
            </div>

            <div class="expires">
                ⏰ This invitation expires on {{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}
            </div>

            <p>As a <strong>{{ $invitation->role }}</strong>, you'll be able to:</p>
            <ul>
                @if($invitation->role === 'admin')
                    <li>Manage project settings and team members</li>
                    <li>Create, edit, and delete content</li>
                    <li>View all project content</li>
                @elseif($invitation->role === 'editor')
                    <li>Create and edit content</li>
                    <li>View all project content</li>
                @else
                    <li>View project content</li>
                @endif
            </ul>
        </div>

        <div class="buttons">
            <a href="{{ $acceptUrl }}" class="btn btn-primary">Accept Invitation</a>
            <a href="{{ $declineUrl }}" class="btn btn-secondary">Decline</a>
        </div>

        <div class="footer">
            <p>
                If you're having trouble with the buttons above, you can copy and paste this URL into your browser:
            </p>
            <p style="word-break: break-all; color: #3b82f6;">{{ $acceptUrl }}</p>
            <p style="margin-top: 20px;">
                If you didn't expect this invitation, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
