# WRK - Intelligent Workspace for Nonprofit Teams

<p align="center">
  <img src="public/favicon.png" width="80" alt="WRK Logo">
</p>

**WRK** is a comprehensive workspace management platform designed for nonprofit organizations. It streamlines project management, meeting intelligence, funder relations, and team collaboration with AI-powered insights.

---

## 🎯 Core Features

### 📊 Dashboard
- **At-a-glance overview** of projects, meetings, and tasks
- **Activity feed** showing recent team actions
- **Quick stats** for active projects, upcoming meetings, and pending tasks
- **Role-based views** (Admin vs Team Member)
- **Reliability banners** that surface AI or calendar sync issues

### 📁 Project Management
- **Hierarchical project structure** with parent/child project nesting
- **Project workspaces** with dedicated tabs for Overview, Documents, Team, and Settings
- **Status tracking** (Planning, Active, On Hold, Completed)
- **Document management** with AI-powered style checking
- **Team member assignment** and role management
- **Grant linkage** to track funding sources per project

### 👥 People Directory
- **Contact management** with organization affiliations
- **Card and table view options** with search and filtering
- **Dynamic avatar colors** based on name
- **LinkedIn integration** for professional profiles
- **Meeting history** tracking per contact
- **Notes and tags** for relationship management

### 🏢 Organizations
- **Organization profiles** with type classification
- **Contact associations** showing all people at an org
- **Meeting history** with the organization
- **Funder designation** for grant-giving organizations

### 📅 Meetings

#### Calendar Integration
- **Google Calendar sync** with OAuth authentication
- **Automatic attendee matching** to People directory
- **Organization linking** via email domain detection
- **Extended date range sync** (past month through Q1 2026)

#### Meeting Views (4 modes)
- **Sections View** - Organized by Upcoming, Needs Notes, Completed
- **List View** - Compact table layout with date, title, org, attendees, status
- **Cards View** - Rich visual cards in responsive grid
- **Kanban View** - Horizontal columns by month (7 months ahead)

#### Video Conference Support
- **Automatic link detection** from Google Meet, Zoom, and Teams
- **One-click join buttons** with platform-specific icons and colors:
  - 🔵 Zoom (blue)
  - 🟢 Google Meet (green)  
  - 🟣 Microsoft Teams (purple)
- **Location display** for in-person meetings

#### Meeting Prep & Notes (NEW!)
- **Meeting Prep** (before meeting)
  - AI-generated preparation briefs via Claude API
  - Attendee analysis with context from previous meetings
  - Suggested discussion topics based on relationship history
  - Key questions to ask
  - Potential asks to identify
  - Relevant project context
  - One-click save to meeting prep notes
- **Meeting Notes** (during/after meeting)
  - @ mentions for people, organizations, and staff
  - Markdown formatting support
  - AI summary generation

#### Post-Meeting Features
- **Key Ask tracking** - What did they ask for?
- **Commitments Made** - What did we promise?
- **Action items** with assignee and due dates
- **Attachment uploads** for related documents

#### Smart Features
- **Quick filter tabs** - All, Upcoming, Needs Notes, Completed with counts
- **Organization and Issue filtering**
- **Search across meetings**
- **User timezone support** - Displays dates in your profile's timezone

### 💰 Funders & Reporting
- **Funder management** with Current and Prospective status
- **Organization autocomplete** when adding funders
- **Grant tracking** with:
  - Grant details (name, amount, dates, status)
  - Editable grant information at any time
  - Document uploads (applications, agreements, reports)
  - AI-powered document insight extraction
  - Reporting requirements and deadlines
  - Linked projects per grant
- **Statistics dashboard** showing:
  - Total funders count
  - Current vs Prospective funders
  - Active funding amounts
  - Reports due

### 📰 Media & Press ✨ NEW

#### Coverage Tracking
- **Press clips database** with URL, outlet, journalist, and publish date
- **Clip types**: Article, Broadcast, Podcast, Opinion, Interview, Mention
- **Sentiment tracking**: Positive, Neutral, Negative, Mixed
- **Staff mentions** - Track who was quoted or interviewed
- **Issue and project linking** for coverage categorization

#### Outbound Pitches
- **Pitch pipeline** with Kanban and list views
- **Status tracking**: Draft → Sent → Following Up → Accepted/Published/Declined
- **Follow-up logging** with dates and notes
- **Journalist and outlet tracking**
- **Success rate analytics** (last 90 days)

#### Inbound Inquiries
- **Media request tracking** with urgency levels (Standard, Urgent, Breaking)
- **Deadline management** with overdue alerts
- **Assignment system** - Take/assign inquiries to team members
- **Response notes** for tracking how we responded
- **Grouped views** by urgency and status

#### Press Contacts
- **Journalist directory** powered by People with `is_journalist` flag
- **Beat tracking** - What topics they cover
- **Responsiveness rating** (High, Medium, Low)
- **Relationship stats** - Clips count, pitches sent, inquiries received

#### Dashboard
- **Coverage stats** - Clips this month vs last, positive coverage rate
- **Top outlets** by coverage volume
- **Needs attention** - Urgent inquiries, stale pitches, pending clip reviews

### 🧠 AI-Powered Features
- **AI Meeting Prep** ✨ NEW
  - Generates comprehensive pre-meeting briefs
  - Analyzes attendees and organization context
  - Suggests topics based on meeting history
  - Identifies key questions and potential asks
  - Pulls relevant project information
- **Grant Document Analysis**
  - Extract funder priorities and values
  - Identify reporting requirements
  - Parse key dates and milestones
  - Surface compliance requirements
- **Document Style Checking**
  - Alignment with organization style guide
  - Suggestions for consistency
- **Report Generation**
  - AI-assisted progress reports
  - Funder-aligned content suggestions
- **Resilient responses**
  - AI chat and project status reuse cached responses when upstream is unavailable
  - AI request metrics logged for success/error/latency

### 👥 Team Hub
- **Team directory** with roles and access levels
- **Admin management** for elevated permissions
- **User profiles** with:
  - Photo, bio, and contact info
  - Timezone preferences (used throughout app)
  - Activity history

### 📚 Knowledge Hub
- **AI-powered search** across meetings, organizations, and projects
- **Dashboard widgets**:
  - This Week - Upcoming meetings by day
  - Needs Attention - Overdue items and meetings needing notes
  - Active Relationships - Most engaged organizations
  - Recent Insights - Topics and decisions
  - Quick Queries - Suggested AI queries
- **Centralized content repository**

---

## 🔐 Security & Access Control

- **Role-based access control** (Admin, Management, Team Member)
- **Visibility settings** per grant (Management Only / All Team)
- **Secure authentication** with Laravel Breeze
- **CSRF protection** and session management
- **API keys via environment variables** (never hardcoded)

---

## 🛠 Technical Stack

- **Backend:** Laravel 12 with Livewire 3
- **Frontend:** Blade templates with Tailwind CSS
- **Database:** SQLite (dev) / PostgreSQL (production)
- **Real-time:** Livewire for reactive UI updates
- **AI Integration:** Anthropic Claude API (Claude Sonnet 4.6)
- **Calendar:** Google Calendar API with OAuth 2.0
- **Queue System:** Database-backed job queue for async processing

---

## 🧭 Architecture Docs

- **Docs index:** `docs/README.md`
- **Operating architecture brief (current):** `docs/WRK_AI_Architecture_Brief_FINAL.md`
- **Data contract:** `docs/WRK_V2_DATA_CONTRACT.md`
- **Schema proposal:** `docs/WRK_V2_SCHEMA_PROPOSAL.md`
- **Box taxonomy and ACL mapping:** `docs/BOX_TAXONOMY_AND_PERMISSION_MAPPING.md`
- **Slack agent MVP scope:** `docs/SLACK_AGENT_MVP_SCOPE.md`

---

## 🚀 Getting Started

```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure required API keys in .env:
# ANTHROPIC_API_KEY=your_key_here
# GOOGLE_CLIENT_ID=your_client_id
# GOOGLE_CLIENT_SECRET=your_client_secret

# Database setup
php artisan migrate
php artisan db:seed

# Start development server
npm run dev
php artisan serve

# Start queue worker (for AI features)
php artisan queue:work
```

---

## 📋 Recent Updates (January 2026)

### Team Accomplishments & Wins System ✨ NEW
- ✅ Track personal and team wins/accomplishments
- ✅ Recognition system for celebrating colleagues
- ✅ Auto-detection of accomplishments from meetings, projects, and documents
- ✅ Impact scoring and activity statistics
- ✅ Emoji reactions on accomplishments
- ✅ Management dashboard with team oversight
- ✅ Detailed teammate profiles with activity history (week/month/quarter/year)
- ✅ Export templates for performance reviews and grant applications

### Message Board Enhancements ✨ NEW
- ✅ Emoji reactions with full picker on messages
- ✅ Threaded replies with nested discussions
- ✅ Screenshot/image upload support in replies
- ✅ Collapsible reply threads

### Staff Activation System ✨ NEW
- ✅ Generate activation links for pre-created staff accounts
- ✅ Admin UI for managing activation tokens
- ✅ Secure password setup flow for new team members
- ✅ Email editing capability for staff members

### Geographic Tagging ✨ NEW
- ✅ Region tagging (North America, LATAM, Caribbean, Africa, Europe, Asia, Middle East, Oceania)
- ✅ Country-level tagging with multi-select
- ✅ US States/Territories tagging
- ✅ Applied to People, Projects, and Organizations

### Timezone & Location Management
- ✅ Timezone confirmation prompt on login (every 7 days)
- ✅ "Change timezone/location" link on dashboard
- ✅ Auto-detection of browser timezone
- ✅ All dates displayed in user's timezone

### Feedback Resolution Tracker ✨ NEW
- ✅ Track resolution status for all feedback items
- ✅ Record resolution descriptions and timestamps
- ✅ Stats on time-to-resolution and completion rates
- ✅ Artisan command for bulk resolution recording

### Meeting Improvements
- ✅ Edit meeting time (not just date)
- ✅ Meeting filters by staff, date, and keywords
- ✅ Meeting agenda system
- ✅ Pause AI extraction feature during note-taking
- ✅ Fixed data loss when navigating meeting screens
- ✅ Fixed attendees/topics not working on meeting create
- ✅ Fixed calendar sync not pulling all meetings

### Project Improvements
- ✅ Project tasks with assignments
- ✅ Milestone editing and undo completion
- ✅ Back arrow navigation to parent project
- ✅ Fixed edit button for sub-projects

### Contacts & Organizations
- ✅ Journalist flag (`is_journalist`) on contacts
- ✅ Add organization inline from contact form
- ✅ Inline contact editing
- ✅ Email field for organizations
- ✅ Clean URL display (without https/www prefixes)

### Grants & Funders
- ✅ Prospective funder status support
- ✅ Fixed grant status save button

### Voice & Recording
- ✅ Fixed voice memo recording/upload
- ✅ Fixed microphone recording indicator

### Knowledge Hub
- ✅ Streamlined to simple AI chatbox interface
- ✅ Removed visual clutter

### UI/UX Refinements
- ✅ Removed quantitative totals from dashboard
- ✅ Clean, minimal metrics page
- ✅ Management section in sidebar navigation
- ✅ Fixed favicon and metadata

---

## 📋 Previous Updates (December 2025)

### Meetings
- ✅ Four meeting view modes (Sections, List, Cards, Kanban)
- ✅ AI Meeting Prep with Claude integration
- ✅ Separate Prep Notes vs Meeting Notes fields
- ✅ Video conference link detection (Zoom, Meet, Teams)
- ✅ User timezone support from profile
- ✅ Back button navigation
- ✅ Refined UI with better toolbar spacing

### Funders & Grants
- ✅ Organization autocomplete for funders
- ✅ Editable grant details after creation
- ✅ Loading states for AI extraction

### Knowledge Hub
- ✅ Complete redesign with widget-based dashboard
- ✅ AI-powered semantic search
- ✅ Quick action queries

### Media & Press
- ✅ Full module implementation with Coverage, Pitches, Inquiries tabs
- ✅ Press clips tracking with sentiment and staff mentions
- ✅ Pitch pipeline with Kanban view
- ✅ Media inquiry management with urgency and deadlines
- ✅ Press contacts directory from People
- ✅ Dashboard with coverage stats and needs attention alerts

### Reliability & Admin
- ✅ Admin Metrics tab (AI health, queues, calendar freshness, document ingestion)
- ✅ Admin Permissions tab to manage access levels and admin flags
- ✅ AI/cache resilience (cached chat/status answers on failure; health banners)
- ✅ Safer link ingestion (domain allow/deny, private host blocking, size caps)

### Beta Feedback System
- ✅ Floating feedback widget on every page (bottom-right)
- ✅ Screenshot attachment support for visual feedback
- ✅ Automatic metadata capture (URL, browser, user context)
- ✅ Admin feedback management panel (Product Refinements)
- ✅ AI-powered feedback analysis with actionable recommendations
- ✅ Status tracking (New → Reviewed → Actionable → Resolved)

---

## 📋 Roadmap

- [ ] Legislative/Appropriations tracking module
- [ ] Email notifications for deadlines
- [ ] Commitment tracking system
- [ ] Enhanced reporting templates
- [ ] Mobile-responsive enhancements
- [ ] Multi-organization support
- [ ] API for external integrations
- [ ] Embedding-based search for higher-recall RAG
- [ ] Alerting hooks (Slack/Email) on AI/queue/calendar faults
- [ ] Feedback analytics dashboard with trends over time
- [ ] Advanced team analytics and productivity insights

---

*Built with ❤️ for nonprofit teams who need powerful tools without enterprise complexity.*
