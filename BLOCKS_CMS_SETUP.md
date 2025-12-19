# Blocks CMS Implementation

This document describes the Blocks CMS implementation and setup instructions.

## Overview

The Blocks CMS allows content to be stored as editable blocks keyed by unique strings, with the frontend rendering blocks by key. There are no "Pages" in the backend at this time.

## Architecture

### Backend (Laravel + Orchid)

- **Models**: `Block`, `UserNote`
- **Migrations**: Blocks and user notes tables with full schema
- **Permissions**: Granular permissions for block management
- **API**: Public content API + user notes API
- **Admin UI**: Orchid screens for block management

### Frontend (Astro)

- **Components**: `Block.astro` (main component), individual block renderers
- **Block Types**: Hero, RichText, Image, CTA, FeatureGrid, HTML
- **User Notes**: Vue component for managing personal notes

## Setup Instructions

### Backend Setup

1. **Run migrations**:
   ```bash
   docker exec -it laravel-backend php artisan migrate
   ```

2. **Seed default blocks**:
   ```bash
   docker exec -it laravel-backend php artisan db:seed --class=DefaultBlocksSeeder
   ```

3. **Grant permissions to admin user** (optional):
   Log into the Orchid admin panel and assign block permissions to users/roles via the Roles & Permissions screen.

### Frontend Setup

1. **Install dependencies**:
   ```bash
   cd frontend
   npm install marked isomorphic-dompurify @astrojs/vue vue
   ```

2. **Add Vue integration to Astro config** (astro.config.mjs):
   ```javascript
   import { defineConfig } from 'astro/config';
   import tailwind from '@tailwindcss/vite';
   import vue from '@astrojs/vue';

   export default defineConfig({
     integrations: [vue()],
     vite: {
       plugins: [tailwind()],
     },
   });
   ```

3. **Set environment variables** (.env):
   ```
   PUBLIC_API_URL=http://localhost:8000
   ```

## Usage

### Rendering Blocks in Astro Pages

```astro
---
import Block from '../components/Block.astro';
---

<html>
  <body>
    <!-- Render a block by key -->
    <Block key="homepage.hero" />
    <Block key="homepage.features" />
    <Block key="homepage.cta" />
  </body>
</html>
```

### Using User Notes Component

```astro
---
import UserNotes from '../components/UserNotes.vue';
---

<html>
  <body>
    <UserNotes client:load />
  </body>
</html>
```

### Creating Blocks via Admin

1. Navigate to **Blocks** in the Orchid admin menu
2. Click **Create Block**
3. Enter a unique key (e.g., `about.intro`)
4. Select a block type
5. Fill in the content fields
6. Save as draft or publish immediately

### Block Types

#### Hero
```json
{
  "headline": "Welcome",
  "subheadline": "Subtitle text",
  "image_url": "https://example.com/image.jpg",
  "ctas": [
    {"label": "Get Started", "url": "/start"}
  ]
}
```

#### Rich Text
```json
{
  "markdown": "# Heading\n\nParagraph with **bold** text."
}
```

#### Image
```json
{
  "url": "https://example.com/image.jpg",
  "alt": "Description",
  "caption": "Optional caption"
}
```

#### CTA
```json
{
  "title": "Call to Action",
  "body": "Description text",
  "button_label": "Click Here",
  "button_url": "/destination",
  "variant": "primary"
}
```

#### Feature Grid
```json
{
  "title": "Features",
  "items": [
    {
      "title": "Feature 1",
      "body": "Description",
      "url": "/feature-1"
    }
  ]
}
```

#### HTML (Restricted)
```json
{
  "html": "<div>Raw HTML content</div>"
}
```

## API Endpoints

### Public Content API

- `GET /api/content/blocks` - List published blocks
- `GET /api/content/blocks/{key}` - Get a specific block
- `GET /api/content/blocks/{key}/preview` - Generate preview URL (auth required)

### User Notes API

- `GET /api/me/notes` - List user's notes
- `POST /api/me/notes` - Create a note
- `PUT /api/me/notes/{id}` - Update a note
- `POST /api/me/notes/{id}/archive` - Archive a note
- `POST /api/me/notes/{id}/unarchive` - Unarchive a note
- `DELETE /api/me/notes/{id}` - Delete a note

## Permissions

- `platform.blocks.view` - View blocks list
- `platform.blocks.create` - Create new blocks
- `platform.blocks.edit` - Edit existing blocks
- `platform.blocks.publish` - Publish/unpublish blocks
- `platform.blocks.delete` - Delete blocks
- `platform.blocks.manage_html` - Create/edit HTML blocks
- `platform.blocks.manage_locked` - Edit locked blocks
- `platform.blocks.manage_visibility` - Change block visibility

## Security Features

1. **Type Registry**: Only known block types are allowed
2. **Permission Gates**: HTML blocks require special permission
3. **Content Sanitization**: Markdown and HTML are sanitized
4. **Visibility Controls**: Blocks can be restricted by authentication/role
5. **Locked Blocks**: Prevent accidental edits
6. **Preview Tokens**: Secure draft preview via signed URLs

## Future Enhancements

- Backend SSR rendering of blocks using frontend renderers
- Media library integration
- Block versioning and revision history
- Block templates and duplication
- Advanced analytics and usage tracking
