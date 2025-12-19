<?php

namespace Database\Seeders;

use App\Models\Block;
use Illuminate\Database\Seeder;

/**
 * Default Blocks Seeder
 *
 * Seeds a small set of default blocks for initial setup.
 * This seeder is idempotent: existing blocks will not be overwritten.
 */
class DefaultBlocksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultBlocks = [
            [
                'key' => 'homepage.hero',
                'type' => 'hero',
                'status' => 'published',
                'data' => [
                    'headline' => 'Welcome to Our Platform',
                    'subheadline' => 'Build amazing things with our Blocks CMS',
                    'image_url' => 'https://via.placeholder.com/1200x600',
                    'ctas' => [
                        [
                            'label' => 'Get Started',
                            'url' => '/get-started',
                        ],
                        [
                            'label' => 'Learn More',
                            'url' => '/about',
                        ],
                    ],
                ],
                'visibility' => null,
                'locked' => false,
            ],

            [
                'key' => 'homepage.cta',
                'type' => 'cta',
                'status' => 'published',
                'data' => [
                    'title' => 'Ready to Get Started?',
                    'body' => 'Join thousands of users already using our platform.',
                    'button_label' => 'Sign Up Now',
                    'button_url' => '/signup',
                    'variant' => 'primary',
                ],
                'visibility' => null,
                'locked' => false,
            ],

            [
                'key' => 'homepage.features',
                'type' => 'featureGrid',
                'status' => 'published',
                'data' => [
                    'title' => 'Why Choose Us',
                    'items' => [
                        [
                            'title' => 'Fast & Reliable',
                            'body' => 'Built with performance in mind, our platform delivers blazing-fast experiences.',
                            'url' => '/features/performance',
                        ],
                        [
                            'title' => 'Easy to Use',
                            'body' => 'Intuitive interface designed for both developers and content editors.',
                            'url' => '/features/usability',
                        ],
                        [
                            'title' => 'Secure by Default',
                            'body' => 'Enterprise-grade security with fine-grained permissions and content sanitization.',
                            'url' => '/features/security',
                        ],
                    ],
                ],
                'visibility' => null,
                'locked' => false,
            ],

            [
                'key' => 'dashboard.welcome',
                'type' => 'richText',
                'status' => 'published',
                'data' => [
                    'markdown' => "# Welcome to Your Dashboard\n\nThis is your personal dashboard where you can manage your content and settings.\n\n## Getting Started\n\n- Create and edit content blocks\n- Manage your personal notes\n- Configure your preferences\n\nNeed help? Check out our [documentation](/docs) or [contact support](/support).",
                ],
                'visibility' => 'auth',
                'locked' => false,
            ],

            [
                'key' => 'help.getting-started',
                'type' => 'richText',
                'status' => 'published',
                'data' => [
                    'markdown' => "# Getting Started with Blocks CMS\n\n## What are Blocks?\n\nBlocks are reusable pieces of content that can be embedded anywhere in your application. Each block has a unique **key** and a **type** that determines its structure and rendering.\n\n## Available Block Types\n\n- **Hero**: Large banner sections with headlines and CTAs\n- **Rich Text**: Markdown-formatted content\n- **Image**: Single images with captions\n- **CTA**: Call-to-action sections\n- **Feature Grid**: Grid layouts for features\n- **HTML**: Raw HTML (restricted to admins)\n\n## Creating Your First Block\n\n1. Navigate to Blocks in the admin menu\n2. Click \"Create Block\"\n3. Choose a unique key (e.g., `about.intro`)\n4. Select a block type\n5. Fill in the content fields\n6. Save as draft or publish immediately\n\nYour block is now available to the frontend via its key!",
                ],
                'visibility' => null,
                'locked' => false,
            ],
        ];

        foreach ($defaultBlocks as $blockData) {
            // Only create if key doesn't exist (idempotent)
            Block::firstOrCreate(
                ['key' => $blockData['key']],
                $blockData
            );
        }

        $this->command->info('Default blocks seeded successfully');
    }
}
