<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpCenterController extends Controller
{
    /**
     * Display the help center page.
     */
    public function index(): View
    {
        // FAQ data structure
        $faqs = [
            [
                'id' => 'getting-started',
                'category' => 'Getting Started',
                'questions' => [
                    [
                        'question' => 'How do I get started with Traction Tracker?',
                        'answer' => 'Getting started is easy! After creating your account, you\'ll be guided through setting up your business profile, adding your team members, and configuring your first metrics. Our setup wizard will walk you through each step.'
                    ],
                    [
                        'question' => 'What is a Business Owner role?',
                        'answer' => 'A Business Owner has full administrative access to their business dashboard. They can manage team members, configure business settings, view all metrics, and access advanced features like data export and business analytics.'
                    ],
                    [
                        'question' => 'How do I invite team members?',
                        'answer' => 'Business Owners can invite team members through the Settings page. Navigate to Settings > Team Management, enter the email addresses of your team members, and they\'ll receive an invitation to join your business.'
                    ]
                ]
            ],
            [
                'id' => 'metrics-tracking',
                'category' => 'Metrics & Tracking',
                'questions' => [
                    [
                        'question' => 'What types of metrics can I track?',
                        'answer' => 'Traction Tracker supports various business metrics including sales data, customer acquisition, revenue tracking, operational KPIs, and custom metrics that you define based on your business needs.'
                    ],
                    [
                        'question' => 'How often should I update my metrics?',
                        'answer' => 'We recommend updating your metrics at least weekly for accurate tracking. However, you can set up automated data imports or update them daily if your business requires more frequent monitoring.'
                    ],
                    [
                        'question' => 'Can I import data from other systems?',
                        'answer' => 'Yes! Traction Tracker supports data import from various formats including CSV, Excel, and integration with popular business tools. Check our integrations page for supported platforms.'
                    ]
                ]
            ],
            [
                'id' => 'account-settings',
                'category' => 'Account & Settings',
                'questions' => [
                    [
                        'question' => 'How do I change my password?',
                        'answer' => 'Go to your Profile page and scroll down to the "Update Password" section. Enter your current password and your new password to update it securely.'
                    ],
                    [
                        'question' => 'Can I customize the dashboard appearance?',
                        'answer' => 'Yes! Business Owners can customize their dashboard through the Settings page. You can change themes, accent colors, upload your company logo, and configure the layout to match your brand.'
                    ],
                    [
                        'question' => 'How do I delete my account?',
                        'answer' => 'Account deletion can be done from your Profile page. Please note that this action is irreversible and will permanently delete all your business data. Consider exporting your data first.'
                    ]
                ]
            ],
            [
                'id' => 'troubleshooting',
                'category' => 'Troubleshooting',
                'questions' => [
                    [
                        'question' => 'I\'m having trouble accessing my dashboard',
                        'answer' => 'First, try clearing your browser cache and cookies. If the issue persists, check if you\'re using a supported browser (Chrome, Firefox, Safari, Edge). Contact support if you still can\'t access your dashboard.'
                    ],
                    [
                        'question' => 'My metrics aren\'t displaying correctly',
                        'answer' => 'Ensure your data format matches our requirements and that you have the necessary permissions to view the metrics. If you\'re a team member, contact your Business Owner to verify your access levels.'
                    ],
                    [
                        'question' => 'How do I report a bug or issue?',
                        'answer' => 'You can report bugs through our WhatsApp support channel (accessible via the chat bubble), email us at support@tractiontracker.com, or use the feedback form in your dashboard.'
                    ]
                ]
            ]
        ];

        $appInfo = [
            'name' => 'Traction Tracker',
            'version' => '1.0.0',
            'description' => 'A comprehensive business intelligence dashboard for tracking your business metrics and KPIs.',
            'features' => [
                'Real-time business metrics tracking',
                'Customizable dashboard with multiple themes',
                'Team collaboration and role management',
                'Data import/export capabilities',
                'Advanced analytics and reporting',
                'Mobile-responsive design',
                'Secure data encryption'
            ]
        ];

        return view('help-center.index', compact('faqs', 'appInfo'));
    }
}
