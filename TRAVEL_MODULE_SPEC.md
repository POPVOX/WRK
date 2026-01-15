# Travel Module Specification for POPVOX WRK

## Overview

The Travel Module provides comprehensive travel management for nonprofit teams, integrating with existing Projects, Organizations, People, and Calendar systems. It supports trip planning, itinerary management, expense tracking, policy compliance, and team coordination.

---

## Table of Contents

1. [Database Schema](#1-database-schema)
2. [Team Member Travel Profile](#2-team-member-travel-profile)
3. [Trip Management](#3-trip-management)
4. [Itinerary & Segments](#4-itinerary--segments)
5. [Expense & Reimbursement Tracking](#5-expense--reimbursement-tracking)
6. [External Sponsorship & Billing](#6-external-sponsorship--billing)
7. [AI-Powered Features](#7-ai-powered-features)
8. [Travel Policy Compliance](#8-travel-policy-compliance)
9. [Documents & Attachments](#9-documents--attachments)
10. [Calendar Integration](#10-calendar-integration)
11. [Analytics & Map Visualization](#11-analytics--map-visualization)
12. [Dashboard & Widgets](#12-dashboard--widgets)
13. [Views & UI Components](#13-views--ui-components)
14. [Permissions & Security](#14-permissions--security)
15. [API Endpoints](#15-api-endpoints)
16. [Implementation Phases](#16-implementation-phases)

---

## 1. Database Schema

### Core Tables

#### `travel_profiles` (extends users table or separate)
Stores sensitive traveler information for team members.

```php
Schema::create('travel_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    // Personal Info
    $table->date('birthday')->nullable();
    $table->text('passport_number_encrypted')->nullable(); // Encrypted at rest
    $table->string('passport_country', 2)->nullable(); // ISO country code
    $table->date('passport_expiration')->nullable();
    
    // Travel Programs (encrypted)
    $table->text('tsa_precheck_number_encrypted')->nullable();
    $table->text('global_entry_number_encrypted')->nullable();
    
    // Frequent Flyer Programs (JSON array, encrypted)
    $table->text('frequent_flyer_programs_encrypted')->nullable();
    // Structure: [{ "airline": "United", "number": "ABC123", "status": "Gold" }]
    
    // Hotel Programs (JSON array, encrypted)
    $table->text('hotel_programs_encrypted')->nullable();
    // Structure: [{ "chain": "Marriott", "number": "123456", "status": "Platinum" }]
    
    // Rental Car Programs (JSON array, encrypted)
    $table->text('rental_car_programs_encrypted')->nullable();
    
    // Preferences
    $table->enum('seat_preference', ['window', 'aisle', 'middle', 'no_preference'])->default('no_preference');
    $table->text('dietary_restrictions')->nullable();
    $table->text('travel_notes')->nullable(); // Any other preferences
    
    // Emergency Contact
    $table->string('emergency_contact_name')->nullable();
    $table->string('emergency_contact_relationship')->nullable();
    $table->string('emergency_contact_phone')->nullable();
    $table->string('emergency_contact_email')->nullable();
    
    $table->timestamps();
});
```

#### `trips`
Core trip entity that groups all travel components.

```php
Schema::create('trips', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // e.g., "NDI Democracy Conference - Nairobi"
    $table->text('description')->nullable();
    
    // Trip Classification
    $table->enum('type', [
        'conference_event',
        'funder_meeting', 
        'site_visit',
        'advocacy_hill_day',
        'training',
        'partner_delegation',
        'board_meeting',
        'speaking_engagement',
        'research',
        'other'
    ])->default('other');
    
    // Status
    $table->enum('status', [
        'planning',      // Initial planning stage
        'booked',        // Travel is booked
        'in_progress',   // Currently traveling
        'completed',     // Trip finished
        'cancelled'      // Trip cancelled
    ])->default('planning');
    
    // Dates (overall trip window)
    $table->date('start_date');
    $table->date('end_date');
    
    // Primary Destination (for display/filtering)
    $table->string('primary_destination_city');
    $table->string('primary_destination_country', 2); // ISO code
    $table->string('primary_destination_region')->nullable(); // From geo tagging system
    
    // Associations
    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
    
    // Policy Compliance
    $table->enum('risk_level', ['standard', 'moderate', 'high', 'prohibited'])->nullable();
    $table->boolean('step_registration_required')->default(false);
    $table->boolean('step_registration_completed')->default(false);
    $table->boolean('travel_insurance_required')->default(false);
    $table->boolean('travel_insurance_confirmed')->default(false);
    $table->boolean('approval_required')->default(false);
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('approved_at')->nullable();
    $table->text('approval_notes')->nullable();
    
    // Partner Organization (for delegations)
    $table->foreignId('partner_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
    $table->string('partner_program_name')->nullable(); // e.g., "World Forum for Democracy 2026"
    
    // Post-Trip
    $table->text('debrief_notes')->nullable();
    $table->text('outcomes')->nullable();
    
    // Metadata
    $table->boolean('is_template')->default(false);
    $table->foreignId('created_from_template_id')->nullable()->constrained('trips')->nullOnDelete();
    
    $table->timestamps();
    $table->softDeletes();
});
```

#### `trip_travelers`
Junction table for trip participants.

```php
Schema::create('trip_travelers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    
    $table->enum('role', ['lead', 'participant'])->default('participant');
    $table->boolean('calendar_events_created')->default(false);
    
    // Individual traveler notes for this trip
    $table->text('personal_notes')->nullable();
    
    $table->timestamps();
    
    $table->unique(['trip_id', 'user_id']);
});
```

#### `trip_destinations`
For multi-leg trips with multiple destinations.

```php
Schema::create('trip_destinations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    
    $table->integer('order')->default(1); // Sequence in trip
    $table->string('city');
    $table->string('state_province')->nullable();
    $table->string('country', 2); // ISO code
    $table->string('region')->nullable();
    
    $table->date('arrival_date');
    $table->date('departure_date');
    
    // Policy info (auto-calculated)
    $table->string('state_dept_level')->nullable(); // 1, 2, 3, 4
    $table->boolean('is_prohibited_destination')->default(false);
    $table->text('travel_advisory_notes')->nullable();
    
    // Coordinates for map
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    
    $table->timestamps();
});
```

#### `trip_segments`
Individual travel segments (flights, trains, etc.)

```php
Schema::create('trip_segments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('trip_destination_id')->nullable()->constrained()->nullOnDelete();
    
    $table->enum('type', [
        'flight',
        'train',
        'bus',
        'rental_car',
        'rideshare',
        'ferry',
        'other_transport'
    ]);
    
    // Common fields
    $table->string('carrier')->nullable(); // Airline, Amtrak, etc.
    $table->string('carrier_code')->nullable(); // UA, AA, etc.
    $table->string('segment_number')->nullable(); // Flight number, train number
    $table->string('confirmation_number')->nullable();
    
    // Departure
    $table->string('departure_location'); // Airport code or city
    $table->string('departure_city')->nullable();
    $table->datetime('departure_datetime');
    $table->string('departure_terminal')->nullable();
    $table->string('departure_gate')->nullable();
    
    // Arrival
    $table->string('arrival_location');
    $table->string('arrival_city')->nullable();
    $table->datetime('arrival_datetime');
    $table->string('arrival_terminal')->nullable();
    
    // Flight-specific
    $table->string('aircraft_type')->nullable();
    $table->string('seat_assignment')->nullable();
    $table->enum('cabin_class', ['economy', 'premium_economy', 'business', 'first'])->nullable();
    $table->integer('distance_miles')->nullable();
    
    // Cost
    $table->decimal('cost', 10, 2)->nullable();
    $table->string('currency', 3)->default('USD');
    
    // Status
    $table->enum('status', ['scheduled', 'confirmed', 'checked_in', 'completed', 'cancelled', 'delayed'])->default('scheduled');
    
    // Booking info
    $table->string('booking_reference')->nullable();
    $table->string('ticket_number')->nullable();
    $table->text('notes')->nullable();
    
    // For AI extraction tracking
    $table->boolean('ai_extracted')->default(false);
    $table->decimal('ai_confidence', 3, 2)->nullable();
    
    $table->timestamps();
});
```

#### `trip_lodging`
Hotel and accommodation tracking.

```php
Schema::create('trip_lodging', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('trip_destination_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('property_name');
    $table->string('chain')->nullable(); // Marriott, Hilton, etc.
    $table->string('address')->nullable();
    $table->string('city');
    $table->string('country', 2);
    
    $table->string('confirmation_number')->nullable();
    $table->date('check_in_date');
    $table->time('check_in_time')->nullable();
    $table->date('check_out_date');
    $table->time('check_out_time')->nullable();
    
    $table->string('room_type')->nullable();
    $table->integer('nights')->nullable();
    $table->decimal('nightly_rate', 10, 2)->nullable();
    $table->decimal('total_cost', 10, 2)->nullable();
    $table->string('currency', 3)->default('USD');
    
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    
    // Coordinates for map
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    
    $table->text('notes')->nullable();
    $table->boolean('ai_extracted')->default(false);
    
    $table->timestamps();
});
```

#### `trip_ground_transport`
Rental cars, parking, local transport.

```php
Schema::create('trip_ground_transport', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('trip_destination_id')->nullable()->constrained()->nullOnDelete();
    
    $table->enum('type', [
        'rental_car',
        'taxi',
        'rideshare',
        'public_transit',
        'shuttle',
        'parking',
        'other'
    ]);
    
    $table->string('provider')->nullable(); // Hertz, Uber, etc.
    $table->string('confirmation_number')->nullable();
    
    $table->datetime('pickup_datetime')->nullable();
    $table->string('pickup_location')->nullable();
    $table->datetime('return_datetime')->nullable();
    $table->string('return_location')->nullable();
    
    // Rental car specific
    $table->string('vehicle_type')->nullable();
    $table->string('license_plate')->nullable();
    
    $table->decimal('cost', 10, 2)->nullable();
    $table->string('currency', 3)->default('USD');
    
    $table->text('notes')->nullable();
    $table->boolean('ai_extracted')->default(false);
    
    $table->timestamps();
});
```

#### `trip_expenses`
Detailed expense tracking by category.

```php
Schema::create('trip_expenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who incurred
    
    $table->enum('category', [
        'airfare',
        'lodging',
        'ground_transport',
        'meals',
        'registration_fees',
        'baggage_fees',
        'wifi_connectivity',
        'tips_gratuities',
        'visa_fees',
        'travel_insurance',
        'office_supplies',
        'other'
    ]);
    
    $table->string('description');
    $table->date('expense_date');
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('USD');
    $table->decimal('amount_usd', 10, 2)->nullable(); // Converted amount
    
    $table->string('vendor')->nullable();
    $table->string('receipt_number')->nullable();
    
    // Reimbursement tracking (internal - from POPVOX to employee)
    $table->enum('reimbursement_status', [
        'not_applicable',  // Org paid directly
        'pending',         // Needs reimbursement
        'submitted',       // Submitted for reimbursement
        'approved',        // Approved for payment
        'paid',           // Reimbursed
        'denied'          // Denied
    ])->default('not_applicable');
    $table->date('reimbursement_submitted_date')->nullable();
    $table->date('reimbursement_paid_date')->nullable();
    
    // Link to sponsorship if externally funded
    $table->foreignId('trip_sponsorship_id')->nullable()->constrained()->nullOnDelete();
    
    $table->text('notes')->nullable();
    
    $table->timestamps();
});
```

#### `trip_sponsorships`
External organization funding/reimbursement.

```php
Schema::create('trip_sponsorships', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    
    $table->enum('type', [
        'full_sponsorship',      // Org pays/reimburses everything
        'partial_sponsorship',   // Org covers specific items
        'travel_only',           // Org covers transport
        'lodging_only',          // Org covers hotel
        'registration_only',     // Org covers event fees
        'honorarium'             // Speaking fee, etc.
    ]);
    
    $table->text('description')->nullable();
    $table->decimal('amount', 10, 2)->nullable(); // Expected amount
    $table->string('currency', 3)->default('USD');
    
    // What's covered
    $table->boolean('covers_airfare')->default(false);
    $table->boolean('covers_lodging')->default(false);
    $table->boolean('covers_ground_transport')->default(false);
    $table->boolean('covers_meals')->default(false);
    $table->boolean('covers_registration')->default(false);
    $table->text('coverage_notes')->nullable();
    
    // Billing/Invoice info (Management only visibility)
    $table->text('billing_instructions')->nullable();
    $table->string('billing_contact_name')->nullable();
    $table->string('billing_contact_email')->nullable();
    $table->string('billing_contact_phone')->nullable();
    $table->text('billing_address')->nullable();
    $table->string('invoice_reference')->nullable();
    $table->string('purchase_order_number')->nullable();
    
    // Payment tracking
    $table->enum('payment_status', [
        'pending',           // Not yet invoiced
        'invoiced',          // Invoice sent
        'partial_payment',   // Partial received
        'paid',             // Fully paid
        'overdue'           // Past due
    ])->default('pending');
    $table->date('invoice_sent_date')->nullable();
    $table->date('payment_due_date')->nullable();
    $table->date('payment_received_date')->nullable();
    $table->decimal('amount_received', 10, 2)->nullable();
    
    $table->text('notes')->nullable();
    
    $table->timestamps();
});
```

#### `trip_events`
Events/meetings associated with the trip.

```php
Schema::create('trip_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    
    // Can link to existing meeting or be standalone
    $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
    
    $table->string('title');
    $table->text('description')->nullable();
    $table->datetime('start_datetime');
    $table->datetime('end_datetime')->nullable();
    $table->string('location')->nullable();
    $table->string('address')->nullable();
    
    $table->enum('type', [
        'conference_session',
        'meeting',
        'presentation',
        'workshop',
        'reception',
        'site_visit',
        'other'
    ])->default('other');
    
    $table->text('notes')->nullable();
    
    $table->timestamps();
});
```

#### `trip_documents`
Attachments and documents.

```php
Schema::create('trip_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
    
    $table->string('filename');
    $table->string('original_filename');
    $table->string('mime_type');
    $table->integer('file_size');
    $table->string('storage_path');
    
    $table->enum('type', [
        'itinerary',
        'confirmation',
        'receipt',
        'invoice',
        'boarding_pass',
        'visa',
        'insurance',
        'agenda',
        'presentation',
        'other'
    ])->default('other');
    
    $table->text('description')->nullable();
    
    // For AI extraction source tracking
    $table->boolean('ai_processed')->default(false);
    $table->timestamp('ai_processed_at')->nullable();
    
    $table->timestamps();
});
```

#### `trip_checklists`
Packing lists and prep items.

```php
Schema::create('trip_checklists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trip_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Null = applies to all
    
    $table->string('item');
    $table->enum('category', [
        'documents',
        'electronics',
        'clothing',
        'presentation_materials',
        'gifts_swag',
        'health_safety',
        'other'
    ])->default('other');
    
    $table->boolean('is_completed')->default(false);
    $table->boolean('ai_suggested')->default(false);
    
    $table->timestamps();
});
```

#### `trip_templates`
Reusable trip templates.

```php
// Trips with is_template = true serve as templates
// When creating from template, copy:
// - Basic info (name pattern, type, description)
// - Default checklist items
// - Typical destinations (without dates)
```

### Indexes

```php
// Performance indexes
$table->index(['trip_id', 'type']); // For trip_segments
$table->index(['start_date', 'end_date']); // For trips
$table->index(['user_id', 'trip_id']); // For trip_travelers
$table->index(['country']); // For destinations, lodging
$table->index(['status']); // For trips, segments
$table->index(['reimbursement_status']); // For expenses
$table->index(['payment_status']); // For sponsorships
```

---

## 2. Team Member Travel Profile

### Location
Accessible from Team Hub > [Team Member] > Travel Profile tab

### Features

#### Profile Fields (Self-Editable)
- **Birthday** - Date picker
- **Passport Information**
  - Passport number (encrypted, masked display: `****1234`)
  - Country of issue
  - Expiration date (with warning if expiring within 6 months)
- **Trusted Traveler Programs**
  - TSA PreCheck number (encrypted)
  - Global Entry number (encrypted)
- **Loyalty Programs** (add multiple)
  - Frequent flyer programs (airline, number, status tier)
  - Hotel programs (chain, number, status tier)
  - Rental car programs (company, number)
- **Preferences**
  - Seat preference (Window / Aisle / Middle / No preference)
  - Dietary restrictions (free text)
  - Other travel notes
- **Emergency Contact**
  - Name
  - Relationship
  - Phone
  - Email

#### Visibility Rules
| Field | Self | Management | Admin | Other Team |
|-------|------|------------|-------|------------|
| Birthday | ✅ View/Edit | ✅ View | ✅ View | ❌ |
| Passport # | ✅ View/Edit | ✅ View (masked) | ✅ View | ❌ |
| TSA/Global Entry | ✅ View/Edit | ✅ View | ✅ View | ❌ |
| Loyalty Programs | ✅ View/Edit | ✅ View | ✅ View | ❌ |
| Preferences | ✅ View/Edit | ✅ View | ✅ View | ❌ |
| Emergency Contact | ✅ View/Edit | ✅ View | ✅ View | ❌ |

#### UI Components
```blade
<!-- Travel Profile Section in User Profile -->
<div class="travel-profile-section">
    <!-- Passport Alert Banner -->
    @if($passportExpiringWithin6Months)
    <x-alert type="warning">
        Your passport expires on {{ $passportExpiration }}. 
        Consider renewing before your next international trip.
    </x-alert>
    @endif
    
    <!-- Sections with edit modals -->
    <x-travel-profile-card title="Passport & Documents" :editable="true">
        <!-- Content -->
    </x-travel-profile-card>
    
    <x-travel-profile-card title="Loyalty Programs" :editable="true">
        <!-- Dynamic list with add/remove -->
    </x-travel-profile-card>
    
    <!-- etc. -->
</div>
```

---

## 3. Trip Management

### Trip List View
Location: Main sidebar > Travel

#### View Modes
1. **List View** - Table with sortable columns
2. **Cards View** - Visual cards in grid
3. **Calendar View** - Monthly calendar with trip blocks
4. **Kanban View** - Columns by status (Planning → Booked → In Progress → Completed)

#### Filters
- Status (Planning, Booked, In Progress, Completed, Cancelled)
- Trip Type (Conference, Funder Meeting, etc.)
- Date Range
- Traveler(s)
- Destination Country/Region
- Project
- Sponsoring Organization

#### Quick Actions
- Create New Trip
- Create from Template
- Duplicate Trip (for recurring travel)

### Trip Detail View

#### Header
```
[Back to Travel] 
┌─────────────────────────────────────────────────────────┐
│ 🇰🇪 NDI Democracy Conference - Nairobi                  │
│ Conference/Event • March 15-22, 2026                    │
│ Status: [Booked ▼]                                      │
│                                                         │
│ [Edit Trip] [Duplicate] [Cancel Trip]                   │
└─────────────────────────────────────────────────────────┘
```

#### Tab Navigation
1. **Overview** - Summary, travelers, dates, policy compliance
2. **Itinerary** - Flights, lodging, transport (with timeline view)
3. **Expenses** - Costs by category, reimbursements
4. **Sponsorship** - External funding, billing info (management only sections)
5. **Events** - Meetings/sessions during trip
6. **Documents** - Attachments, receipts, confirmations
7. **Checklist** - Packing/prep items
8. **Notes** - Debrief, outcomes

### Trip Creation Flow

#### Step 1: Basic Info
```
Trip Name: [_________________________________]
Trip Type: [Conference/Event ▼]
Purpose/Description: [________________________]
                     [________________________]

Primary Destination:
  City: [____________] Country: [▼ Select]
  
Dates:
  Start: [📅] End: [📅]
  
Associated Project: [▼ Select or None]
Partner Organization: [▼ Select or None] (for delegations)
```

#### Step 2: Travelers
```
Who's traveling?
[🔍 Search team members...]

Selected:
☑ Marci Harris (Trip Lead)
☑ Anne Meeker
☑ John Smith

[+ Add External Traveler] (for non-staff like board members)
```

#### Step 3: Policy Compliance Check
*Auto-calculated based on destination*
```
┌─────────────────────────────────────────────────────────┐
│ ⚠️ Travel Policy Requirements                           │
│                                                         │
│ Kenya is a Level 2 (Exercise Increased Caution) country │
│                                                         │
│ Required Actions:                                       │
│ ☐ Submit notification 14 days in advance               │
│ ☐ Register with STEP (step.state.gov)                  │
│ ☐ Verify travel insurance coverage                     │
│                                                         │
│ [View Full Travel Policy] [View State Dept Advisory]    │
└─────────────────────────────────────────────────────────┘
```

#### Step 4: Import Itinerary (Optional)
```
Add your travel details now or later

[📄 Paste Itinerary Text]  [📎 Upload Document]  [Skip for Now]
```

---

## 4. Itinerary & Segments

### Timeline View
Visual chronological display of all trip components.

```
March 15, 2026 (Saturday)
├─ 06:00 AM  ✈️ UA 234 DCA → IAD → NBO
│            Depart: 6:00 AM • Arrive: +1 day 8:30 PM
│            Confirmation: ABC123
│            Seat: 14A (Window)
│
March 16, 2026 (Sunday)  
├─ 08:30 PM  📍 Arrive Nairobi (NBO)
├─ 09:30 PM  🚗 Airport Transfer → Hotel
│            Provider: Conference Shuttle
│
├─ 10:30 PM  🏨 Check In: Sarova Stanley Hotel
│            Confirmation: HTL456
│            Room: Standard King
│            3 nights (Mar 16-19)
│
March 17, 2026 (Monday)
├─ 09:00 AM  📅 Conference Opening Session
│            Location: KICC Main Hall
...
```

### Segment Entry Forms

#### Flight Segment
```
┌─ Add Flight ─────────────────────────────────────────┐
│                                                       │
│ Airline: [United ▼]  Flight #: [UA ____]            │
│                                                       │
│ Departure                    Arrival                  │
│ Airport: [DCA ▼]            Airport: [NBO ▼]        │
│ Date: [📅 Mar 15]           Date: [📅 Mar 16]       │
│ Time: [06:00 AM]            Time: [08:30 PM]        │
│ Terminal: [___]             Terminal: [___]          │
│                                                       │
│ Confirmation #: [____________]                        │
│ Seat: [____]  Class: [Economy ▼]                    │
│ Cost: [$_______]                                     │
│                                                       │
│ Notes: [_________________________________]           │
│                                                       │
│              [Cancel] [Save Flight]                   │
└───────────────────────────────────────────────────────┘
```

#### Lodging Entry
```
┌─ Add Lodging ────────────────────────────────────────┐
│                                                       │
│ Property Name: [________________________]             │
│ Hotel Chain: [▼ Select or type]                      │
│                                                       │
│ Address: [__________________________________]         │
│ City: [___________]  Country: [▼ Kenya]             │
│                                                       │
│ Check In                     Check Out                │
│ Date: [📅 Mar 16]           Date: [📅 Mar 19]       │
│ Time: [3:00 PM]             Time: [11:00 AM]        │
│                                                       │
│ Confirmation #: [____________]                        │
│ Room Type: [____________]                             │
│ Nightly Rate: [$_______] × [3] nights = $______     │
│                                                       │
│ Contact Phone: [____________]                         │
│ Notes: [_________________________________]           │
│                                                       │
│              [Cancel] [Save Lodging]                  │
└───────────────────────────────────────────────────────┘
```

### Conflict Detection
System automatically checks for:
- Overlapping flights for same traveler
- Double-booked hotels
- Arrival time vs. meeting time conflicts
- Travelers on multiple trips simultaneously

```
⚠️ Scheduling Conflict Detected
Anne Meeker is already traveling March 18-20 (Board Retreat - NYC)
[View Conflict] [Continue Anyway]
```

---

## 5. Expense & Reimbursement Tracking

### Expense Entry

#### Quick Add
```
┌─ Add Expense ────────────────────────────────────────┐
│                                                       │
│ Category: [▼ Airfare]                                │
│ Description: [Round trip DCA-NBO____________]        │
│ Amount: [$1,847.00]  Currency: [USD ▼]              │
│ Date: [📅 Mar 15, 2026]                             │
│ Vendor: [United Airlines______________]              │
│                                                       │
│ Receipt: [📎 Upload] or [📷 Take Photo]             │
│                                                       │
│ Paid By:                                             │
│ ○ Organization (direct payment)                      │
│ ● Team Member (needs reimbursement)                  │
│   Reimbursement Status: [Pending ▼]                 │
│                                                       │
│ Covered by Sponsorship?                              │
│ ☑ Yes - [▼ NDI - Travel Grant]                      │
│                                                       │
│              [Cancel] [Save Expense]                  │
└───────────────────────────────────────────────────────┘
```

### Expense Summary View

```
Trip: NDI Democracy Conference - Nairobi
Total Expenses: $4,523.47

┌─────────────────────────────────────────────────────────┐
│ By Category                    │ By Funding Source      │
│ ══════════════                 │ ══════════════════     │
│ ✈️ Airfare        $1,847.00   │ POPVOX      $1,523.47  │
│ 🏨 Lodging          $892.00   │ NDI Grant   $3,000.00  │
│ 🚗 Ground Trans     $234.00   │                        │
│ 🍽️ Meals           $345.47   │                        │
│ 📋 Registration     $500.00   │                        │
│ 📦 Other           $705.00   │                        │
└─────────────────────────────────────────────────────────┘

Reimbursement Status:
┌────────────────────────────────────────────────────┐
│ To Team Members          │ From Sponsors          │
│ ─────────────────        │ ─────────────────      │
│ Pending:     $345.47     │ Invoiced:  $3,000.00  │
│ Submitted:   $0.00       │ Received:  $0.00      │
│ Paid:        $0.00       │                        │
└────────────────────────────────────────────────────┘
```

### Budget vs. Actual
```
Category         Budget    Actual    Variance
─────────────────────────────────────────────
Airfare         $2,000    $1,847     +$153 ✓
Lodging         $1,000    $892       +$108 ✓
Ground Trans    $300      $234       +$66 ✓
Meals           $400      $345       +$55 ✓
Registration    $500      $500       $0 ✓
Other           $500      $705       -$205 ⚠️
─────────────────────────────────────────────
TOTAL           $4,700    $4,523     +$177 ✓
```

---

## 6. External Sponsorship & Billing

### Sponsorship Section (Tab in Trip Detail)

#### Adding Sponsorship
```
┌─ Add Sponsorship ────────────────────────────────────┐
│                                                       │
│ Sponsoring Organization: [🔍 NDI___________]         │
│                                                       │
│ Type: [▼ Partial Sponsorship]                        │
│                                                       │
│ What's Covered:                                       │
│ ☑ Airfare                                            │
│ ☑ Lodging                                            │
│ ☐ Ground Transportation                              │
│ ☐ Meals                                              │
│ ☑ Registration/Conference Fees                       │
│                                                       │
│ Expected Amount: [$3,000.00]                         │
│                                                       │
│ Coverage Notes:                                       │
│ [Covers economy airfare, standard hotel (up to       │
│  $200/night), and conference registration_______]    │
│                                                       │
│              [Cancel] [Save Sponsorship]              │
└───────────────────────────────────────────────────────┘
```

#### Billing Information (Management Only)
```
┌─ Billing Details ─────────────────────── 🔒 Management │
│                                                        │
│ Invoice Instructions:                                  │
│ [Submit invoice to grants@ndi.org within 30 days of   │
│  travel completion. Reference grant #NDI-2026-0142.   │
│  Include itemized receipts for all expenses._______]  │
│                                                        │
│ Billing Contact:                                       │
│ Name: [Sarah Johnson________________]                  │
│ Email: [sjohnson@ndi.org___________]                  │
│ Phone: [(202) 555-0123_____________]                  │
│                                                        │
│ Billing Address:                                       │
│ [NDI Finance Department                               │
│  455 Massachusetts Ave NW                             │
│  Washington, DC 20001______________]                  │
│                                                        │
│ PO Number: [NDI-PO-2026-0892]                         │
│                                                        │
│ ─────────────────────────────────────────────         │
│ Payment Tracking                                       │
│                                                        │
│ Status: [▼ Invoiced]                                  │
│ Invoice Sent: [📅 Mar 25, 2026]                       │
│ Payment Due: [📅 Apr 24, 2026]                        │
│ Amount: [$3,000.00]                                   │
│ Received: [$0.00]                                     │
│                                                        │
│ Notes:                                                 │
│ [Invoice #INV-2026-0342 sent via email_________]      │
└────────────────────────────────────────────────────────┘
```

---

## 7. AI-Powered Features

### 7.1 Itinerary Extraction

#### Input Methods
1. **Paste Text** - Paste email confirmations, itinerary text
2. **Upload Document** - PDF, image of confirmation
3. **Forward Email** - Future: dedicated email inbox

#### Processing Flow
```
User pastes/uploads itinerary
         ↓
    AI Extraction Job (queued)
         ↓
    Claude API Call with structured extraction prompt
         ↓
    Parse response into segments
         ↓
    Present for user review/confirmation
         ↓
    Save confirmed segments
```

#### Extraction Prompt Template
```
You are extracting travel itinerary information. Parse the following text/document and extract structured data.

Return JSON with the following structure:
{
  "flights": [
    {
      "carrier": "United Airlines",
      "carrier_code": "UA",
      "flight_number": "234",
      "departure_airport": "DCA",
      "departure_city": "Washington",
      "departure_datetime": "2026-03-15T06:00:00",
      "arrival_airport": "NBO",
      "arrival_city": "Nairobi",
      "arrival_datetime": "2026-03-16T20:30:00",
      "confirmation_number": "ABC123",
      "seat": "14A",
      "cabin_class": "economy",
      "cost": 1847.00,
      "confidence": 0.95
    }
  ],
  "lodging": [...],
  "ground_transport": [...],
  "events": [...]
}

For each field, include a confidence score (0-1).
If information is ambiguous or missing, set confidence lower.
Mark required fields you couldn't find.

--- BEGIN ITINERARY ---
{itinerary_content}
--- END ITINERARY ---
```

#### Review UI
```
┌─ Review Extracted Itinerary ─────────────────────────┐
│                                                       │
│ We found the following travel details:               │
│                                                       │
│ ✈️ FLIGHTS                                           │
│ ┌─────────────────────────────────────────────────┐ │
│ │ ✓ UA 234: DCA → NBO                             │ │
│ │   Mar 15, 6:00 AM → Mar 16, 8:30 PM            │ │
│ │   Confirmation: ABC123                          │ │
│ │   Confidence: ████████░░ 85%                   │ │
│ │   [Edit] [Remove]                               │ │
│ └─────────────────────────────────────────────────┘ │
│                                                       │
│ 🏨 LODGING                                           │
│ ┌─────────────────────────────────────────────────┐ │
│ │ ✓ Sarova Stanley Hotel, Nairobi                 │ │
│ │   Mar 16-19 (3 nights)                         │ │
│ │   Confirmation: HTL456                          │ │
│ │   ⚠️ Rate not found - please add               │ │
│ │   Confidence: ██████░░░░ 60%                   │ │
│ │   [Edit] [Remove]                               │ │
│ └─────────────────────────────────────────────────┘ │
│                                                       │
│ ⚠️ Some items need review (highlighted in yellow)    │
│                                                       │
│    [Cancel]  [Save All]  [Save & Continue Editing]   │
└───────────────────────────────────────────────────────┘
```

### 7.2 Trip Prep Suggestions (AI-Powered Checklist)

When a trip is created, generate contextual checklist items.

#### Prompt Template
```
Generate a trip preparation checklist for the following trip:

Trip: {trip_name}
Type: {trip_type}
Destination: {destination_city}, {destination_country}
Duration: {duration} days
Travelers: {traveler_names}
Purpose: {description}
Events: {associated_events}

Consider:
1. Documents needed (passport validity, visas, travel authorizations)
2. Electronics and tech (adapters, chargers, devices)
3. Presentation materials if speaking/presenting
4. Health items (medications, vaccinations)
5. Organization-specific materials (business cards, swag, reports)
6. Weather-appropriate clothing
7. Any destination-specific items

Return a JSON array of checklist items with categories.
```

#### Example Generated Checklist
```
Trip Prep Checklist for: NDI Conference - Nairobi

📄 Documents
☐ Verify passport valid 6+ months past travel date
☐ Print conference registration confirmation
☐ Carry copy of travel insurance policy
☐ Complete STEP registration (required per travel policy)

💻 Electronics  
☐ Power adapter (Kenya uses Type G plugs)
☐ Portable charger / power bank
☐ Laptop with presentation files
☐ Download offline maps for Nairobi

📊 Presentation Materials
☐ Upload slides to cloud backup
☐ Bring USB drive with presentation
☐ Print handouts (25 copies recommended)
☐ POPVOX business cards

🏥 Health & Safety
☐ Check CDC recommendations for Kenya
☐ Pack any prescription medications
☐ Hand sanitizer and masks

👔 Clothing
☐ Business casual for conference sessions
☐ Comfortable shoes for walking
☐ Light layers (Nairobi is ~70°F in March)

📦 Organization Materials  
☐ POPVOX Foundation one-pagers
☐ Partner organization contact list
☐ Emergency contact card (per travel policy)

[+ Add Custom Item]  [🤖 Regenerate Suggestions]
```

### 7.3 Post-Trip Debrief Prompts

When trip status changes to "Completed", prompt for debrief.

```
┌─ Trip Debrief ───────────────────────────────────────┐
│                                                       │
│ Your trip to Nairobi is complete! Take a moment to   │
│ capture key takeaways.                               │
│                                                       │
│ What were the main outcomes of this trip?            │
│ [_____________________________________________]      │
│ [_____________________________________________]      │
│                                                       │
│ Key contacts made: (link to People)                  │
│ [🔍 Search contacts...] [+ Add new contact]          │
│ • Sarah Kimani, NDI Kenya                            │
│ • James Oduor, Parliament of Kenya                   │
│                                                       │
│ Follow-up actions needed:                            │
│ [_____________________________________________]      │
│                                                       │
│ Would you recommend this event for future years?     │
│ ○ Yes, definitely  ○ Maybe  ○ No                    │
│                                                       │
│ Any issues to flag for future travel?                │
│ [_____________________________________________]      │
│                                                       │
│             [Skip for Now]  [Save Debrief]           │
└───────────────────────────────────────────────────────┘
```

---

## 8. Travel Policy Compliance

### Automatic Risk Assessment

When destination is selected, system automatically:
1. Looks up State Department travel advisory level
2. Checks against prohibited countries list
3. Determines notification/approval requirements
4. Sets required compliance checkboxes

#### Data Source
- Maintain internal table of country risk levels
- Seed from State Department data
- Admin can update as advisories change
- Alternatively: API integration with travel advisory service

#### `country_travel_advisories` Table
```php
Schema::create('country_travel_advisories', function (Blueprint $table) {
    $table->id();
    $table->string('country_code', 2)->unique(); // ISO code
    $table->string('country_name');
    $table->enum('advisory_level', ['1', '2', '3', '4']);
    $table->string('advisory_title'); // "Exercise Normal Precautions", etc.
    $table->boolean('is_prohibited')->default(false); // Russia, China, Iran, NK
    $table->text('advisory_summary')->nullable();
    $table->string('state_dept_url')->nullable();
    $table->timestamp('last_updated');
    $table->timestamps();
});
```

### Compliance UI in Trip Creation

```
┌─ Travel Policy Compliance ───────────────────────────┐
│                                                       │
│ Destination: Nairobi, Kenya 🇰🇪                       │
│ Advisory Level: 2 - Exercise Increased Caution       │
│                                                       │
│ ╔═══════════════════════════════════════════════════╗│
│ ║ REQUIRED ACTIONS                                  ║│
│ ╠═══════════════════════════════════════════════════╣│
│ ║                                                   ║│
│ ║ ☐ Submit notification 14 days before travel      ║│
│ ║   Due: March 1, 2026                             ║│
│ ║   Notify: Direct supervisor + Managing Director   ║│
│ ║                                                   ║│
│ ║ ☐ Register with STEP (Smart Traveler Program)    ║│
│ ║   → step.state.gov                               ║│
│ ║                                                   ║│
│ ║ ☐ Verify international travel insurance          ║│
│ ║   Confirm coverage includes Kenya                 ║│
│ ║                                                   ║│
│ ╚═══════════════════════════════════════════════════╝│
│                                                       │
│ 📋 View Full POPVOX Travel Policy                    │
│ 🌐 View State Department Kenya Advisory              │
│                                                       │
│ This is a partner delegation (NDI)                   │
│ ☑ Partner organization has provided security brief  │
│                                                       │
└───────────────────────────────────────────────────────┘
```

### Prohibited Destination Warning

```
┌─ ⛔ PROHIBITED DESTINATION ──────────────────────────┐
│                                                       │
│ Travel to China is PROHIBITED under POPVOX           │
│ Foundation travel policy without written exception   │
│ from the Executive Director.                         │
│                                                       │
│ This restriction applies to both organizational      │
│ and personal travel during employment.               │
│                                                       │
│ To request an exception:                             │
│ 1. Prepare detailed business justification           │
│ 2. Complete risk assessment                          │
│ 3. Develop enhanced security protocols               │
│ 4. Submit to Executive Director for review           │
│                                                       │
│ [Cancel Trip]  [Request Exception]                   │
└───────────────────────────────────────────────────────┘
```

### Compliance Dashboard (Admin View)

```
Travel Compliance Overview

Upcoming Travel Requiring Action:
┌────────────────────────────────────────────────────────┐
│ Trip                  │ Traveler │ Status    │ Due    │
│───────────────────────┼──────────┼───────────┼────────│
│ NDI Nairobi          │ Marci H. │ ⚠️ STEP    │ Mar 1  │
│ Brussels EU Summit   │ Anne M.  │ ✓ Complete│ --     │
│ Mexico City Partner  │ John S.  │ ⚠️ Notify │ Feb 20 │
└────────────────────────────────────────────────────────┘

Passport Expirations:
┌────────────────────────────────────────────────────────┐
│ Team Member │ Expires    │ Status                     │
│─────────────┼────────────┼────────────────────────────│
│ Jane Doe    │ Apr 2026   │ ⚠️ Expires in 3 months    │
│ Bob Smith   │ Dec 2026   │ ✓ Valid                   │
└────────────────────────────────────────────────────────┘
```

---

## 9. Documents & Attachments

### Document Types
- Itinerary (PDF, email)
- Booking confirmations
- Receipts
- Invoices
- Boarding passes
- Visa documents
- Travel insurance policy
- Event agendas
- Presentations
- Trip reports

### Upload UI
```
┌─ Trip Documents ─────────────────────────────────────┐
│                                                       │
│ [📎 Upload Document]  [📷 Scan Receipt]              │
│                                                       │
│ ┌─────────────────────────────────────────────────┐ │
│ │ 📄 United_Confirmation.pdf                      │ │
│ │    Type: Confirmation • Uploaded Mar 1          │ │
│ │    [View] [Download] [🤖 Extract Details] [🗑️] │ │
│ └─────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────┐ │
│ │ 📄 Hotel_Receipt.jpg                            │ │
│ │    Type: Receipt • Uploaded Mar 20              │ │
│ │    [View] [Download] [🗑️]                      │ │
│ └─────────────────────────────────────────────────┘ │
│                                                       │
└───────────────────────────────────────────────────────┘
```

### AI Document Processing
When "Extract Details" is clicked on a document:
1. Document sent to Claude API with extraction prompt
2. Results populate itinerary segments
3. User reviews and confirms

---

## 10. Calendar Integration

### Automatic Calendar Events

When a trip is booked and travelers are assigned:

#### Events Created
1. **Travel Block** - All-day event(s) spanning trip dates
   - Title: "🌍 Travel: {Trip Name}"
   - Shows on team calendar as busy/OOO
   
2. **Flight Segments** - Individual flight events
   - Title: "✈️ {Carrier} {Flight#}: {Origin} → {Dest}"
   - Start: Departure time
   - End: Arrival time
   - Location: Departure airport
   - Description: Confirmation #, seat, etc.
   
3. **Lodging Check-in/out**
   - Title: "🏨 Check In: {Hotel Name}" / "Check Out"
   - Time: Check-in/out time
   
4. **Trip Events** - Meetings/sessions
   - If linked to existing meeting, update that event
   - If standalone, create new event

#### Implementation
```php
// Job: CreateTripCalendarEvents
class CreateTripCalendarEvents implements ShouldQueue
{
    public function handle(Trip $trip)
    {
        foreach ($trip->travelers as $traveler) {
            // Check if user has Google Calendar connected
            if (!$traveler->user->hasGoogleCalendarConnection()) {
                continue;
            }
            
            // Create travel block
            $this->createTravelBlock($trip, $traveler);
            
            // Create flight events
            foreach ($trip->segments()->where('type', 'flight')->get() as $flight) {
                $this->createFlightEvent($flight, $traveler);
            }
            
            // Create lodging events
            foreach ($trip->lodging as $lodging) {
                $this->createLodgingEvents($lodging, $traveler);
            }
            
            // Mark as synced
            $traveler->update(['calendar_events_created' => true]);
        }
    }
}
```

### Out-of-Office Visibility

In Team Hub, show who's traveling:

```
Currently Traveling:
┌────────────────────────────────────────────────────────┐
│ 🌍 Marci Harris - Nairobi, Kenya (Mar 15-22)          │
│    NDI Democracy Conference                            │
│ 🌍 John Smith - Mexico City (Mar 18-20)               │
│    Partner Site Visit                                  │
└────────────────────────────────────────────────────────┘

Upcoming Travel (Next 14 Days):
┌────────────────────────────────────────────────────────┐
│ Mar 25-27  Anne Meeker - NYC (Board Retreat)          │
│ Apr 2-5    Marci Harris - Brussels (EU Summit)        │
└────────────────────────────────────────────────────────┘
```

---

## 11. Analytics & Map Visualization

### Travel Statistics Dashboard

```
┌─ Team Travel Analytics ──────────────────────────────┐
│                                                       │
│ 📊 2026 Overview                     [Date Range ▼]  │
│                                                       │
│ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐     │
│ │     12      │ │   47,823    │ │  $34,521    │     │
│ │   Trips     │ │    Miles    │ │  Expenses   │     │
│ │  ↑3 vs 2025 │ │ ↑12% vs '25 │ │ ↓8% vs '25  │     │
│ └─────────────┘ └─────────────┘ └─────────────┘     │
│                                                       │
│ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐     │
│ │     8       │ │     15      │ │     5       │     │
│ │  Countries  │ │   Cities    │ │  Projects   │     │
│ │  Visited    │ │  Visited    │ │  Supported  │     │
│ └─────────────┘ └─────────────┘ └─────────────┘     │
│                                                       │
└───────────────────────────────────────────────────────┘
```

### Interactive Map

Using Leaflet.js or Mapbox for the map visualization.

```
┌─ Where We've Been ───────────────────────────────────┐
│                                                       │
│ Filters: [All Time ▼] [All Team ▼] [All Projects ▼] │
│                                                       │
│ ┌─────────────────────────────────────────────────┐ │
│ │                                                 │ │
│ │     🌍 Interactive World Map                   │ │
│ │                                                 │ │
│ │  • Markers at each destination                 │ │
│ │  • Size = number of trips                      │ │
│ │  • Color = trip type                           │ │
│ │  • Click for trip details                      │ │
│ │  • Lines connecting multi-leg trips            │ │
│ │                                                 │ │
│ └─────────────────────────────────────────────────┘ │
│                                                       │
│ Legend:                                              │
│ 🔵 Conference  🟢 Funder Meeting  🟡 Site Visit     │
│ 🟣 Advocacy    🟠 Training        ⚪ Other           │
│                                                       │
└───────────────────────────────────────────────────────┘
```

### Trip Analytics by Category

```
Trips by Type (2026)
══════════════════════════════════════════
Conference/Event    ████████████░░░░  8
Funder Meeting      ████████░░░░░░░░  5
Site Visit          ████░░░░░░░░░░░░  3
Advocacy/Hill Day   ██████░░░░░░░░░░  4
Partner Delegation  ████░░░░░░░░░░░░  2

Top Destinations
══════════════════════════════════════════
1. Washington, DC     12 trips
2. New York, NY        6 trips
3. Brussels, Belgium   4 trips
4. Nairobi, Kenya      2 trips
5. Mexico City         2 trips

Travel by Team Member
══════════════════════════════════════════
Marci Harris    ████████████████  15 trips
Anne Meeker     ████████████░░░░  10 trips
John Smith      ████████░░░░░░░░   6 trips
Jane Doe        ████░░░░░░░░░░░░   3 trips

Expenses by Category (YTD)
══════════════════════════════════════════
✈️ Airfare         $18,234  (52%)
🏨 Lodging          $9,456  (27%)
🚗 Ground Trans     $3,234  (9%)
🍽️ Meals           $2,567  (7%)
📋 Registration    $1,030  (3%)
📦 Other             $800  (2%)
```

### Project/Grant Travel Reports

```
Travel Expenses by Project
══════════════════════════════════════════════════════
Project                          Trips   Expenses
──────────────────────────────────────────────────────
Democracy Tech Initiative          5     $12,456
Congressional Modernization        8     $8,234
International Partnerships         3     $7,891
General/Administrative             4     $5,940

Travel Expenses by Grant/Funder
══════════════════════════════════════════════════════
Funder                   Grant              Expenses
──────────────────────────────────────────────────────
NDI                      Tech Democracy     $8,234
Hewlett Foundation       General Support    $5,456
Knight Foundation        Civic Tech         $3,891
(Self-funded)            --                $16,940
```

---

## 12. Dashboard & Widgets

### Main Dashboard Widget

```
┌─ Upcoming Travel ─────────────────────── View All → │
│                                                      │
│ 📅 This Week                                        │
│ ├─ Mar 15-22: Marci H. → Nairobi 🇰🇪               │
│ │   NDI Democracy Conference                        │
│ │   ⚠️ STEP registration pending                   │
│ │                                                   │
│ └─ Mar 18-20: John S. → Mexico City 🇲🇽           │
│     Partner Site Visit                              │
│                                                      │
│ 📅 Next Week                                        │
│ └─ Mar 25-27: Anne M. → NYC                        │
│     Board Retreat                                   │
│                                                      │
└──────────────────────────────────────────────────────┘
```

### Team Hub Integration

In Team Hub sidebar or main view:

```
Who's Out
─────────────────────
🌍 Traveling
   Marci H. (Nairobi, Mar 15-22)
   John S. (Mexico City, Mar 18-20)

📅 Upcoming
   Anne M. → NYC (Mar 25-27)
```

### Travel Alerts Widget

```
┌─ Travel Alerts ──────────────────────────────────────┐
│                                                       │
│ ⚠️ Action Required                                   │
│ ├─ Marci: Complete STEP registration (due Mar 1)    │
│ ├─ John: Submit travel notification (due Mar 4)     │
│ └─ Jane: Passport expires in 4 months              │
│                                                       │
│ 📋 Pending Reimbursements                           │
│ └─ 3 expenses totaling $892 awaiting approval       │
│                                                       │
│ 💰 Outstanding Invoices                             │
│ └─ NDI: $3,000 (invoiced Mar 25, due Apr 24)       │
│                                                       │
└───────────────────────────────────────────────────────┘
```

---

## 13. Views & UI Components

### Sidebar Navigation

```
📊 Dashboard
📁 Projects
👥 People
🏢 Organizations
📅 Meetings
💰 Funders
📰 Media
🌍 Travel           ← NEW
   ├─ All Trips
   ├─ Calendar
   ├─ Analytics
   └─ Templates
🧠 Knowledge Hub
👤 Team Hub
⚙️ Settings
```

### Main Travel Page Tabs

```
┌─────────────────────────────────────────────────────────┐
│ 🌍 Travel                                               │
│                                                         │
│ [All Trips] [Upcoming] [My Trips] [Analytics] [Map]    │
│                                                         │
│ ┌──────────────────────────────────────────────────┐  │
│ │ + New Trip    🔍 Search...    Filters ▼  View ▼ │  │
│ └──────────────────────────────────────────────────┘  │
│                                                         │
│ ... content based on selected tab ...                   │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Trip Card Component

```blade
<x-trip-card :trip="$trip">
    <!-- Renders as -->
    <div class="trip-card">
        <div class="trip-header">
            <span class="trip-flag">🇰🇪</span>
            <h3>NDI Democracy Conference</h3>
            <span class="trip-status badge-booked">Booked</span>
        </div>
        <div class="trip-meta">
            <span>📍 Nairobi, Kenya</span>
            <span>📅 Mar 15-22, 2026</span>
        </div>
        <div class="trip-travelers">
            <x-avatar-stack :users="$trip->travelers" :limit="3" />
        </div>
        <div class="trip-footer">
            <span class="trip-type">Conference</span>
            <span class="trip-project">Democracy Tech</span>
        </div>
    </div>
</x-trip-card>
```

### Livewire Components

```
app/Livewire/Travel/
├── TripIndex.php           # Main trip list with filters
├── TripDetail.php          # Trip detail view
├── TripCreate.php          # Multi-step trip creation
├── TripItinerary.php       # Itinerary management
├── TripExpenses.php        # Expense tracking
├── TripSponsorship.php     # Sponsorship management
├── TripDocuments.php       # Document uploads
├── TripChecklist.php       # Prep checklist
├── TravelProfile.php       # User travel profile
├── TravelAnalytics.php     # Analytics dashboard
├── TravelMap.php           # Interactive map
├── TravelCalendar.php      # Calendar view
├── ItineraryExtractor.php  # AI extraction UI
└── Components/
    ├── TripCard.php
    ├── SegmentTimeline.php
    ├── ExpenseSummary.php
    └── ComplianceChecklist.php
```

---

## 14. Permissions & Security

### Role-Based Access

| Feature | Team Member | Management | Admin |
|---------|-------------|------------|-------|
| View all trips | ✅ | ✅ | ✅ |
| Create trips | ✅ | ✅ | ✅ |
| Edit own trips | ✅ | ✅ | ✅ |
| Edit any trip | ❌ | ✅ | ✅ |
| View sponsorship billing | ❌ | ✅ | ✅ |
| Edit sponsorship billing | ❌ | ✅ | ✅ |
| Approve reimbursements | ❌ | ✅ | ✅ |
| View other's travel profiles | ❌ | ✅ (masked) | ✅ |
| Edit travel advisories | ❌ | ❌ | ✅ |
| View compliance dashboard | ❌ | ✅ | ✅ |
| Manage trip templates | ❌ | ✅ | ✅ |

### Sensitive Data Encryption

```php
// Model: TravelProfile
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class TravelProfile extends Model
{
    protected function passportNumber(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }
    
    // Masked display for management view
    public function getMaskedPassportAttribute(): ?string
    {
        $passport = $this->passport_number;
        if (!$passport) return null;
        return '****' . substr($passport, -4);
    }
    
    // Similar for TSA, Global Entry, frequent flyer numbers
}
```

### Audit Logging

Log access to sensitive travel profile data:

```php
// Middleware or Observer
TravelProfileAccessed::dispatch(
    auth()->user(),
    $travelProfile,
    'viewed_passport_number'
);
```

---

## 15. API Endpoints

### Routes

```php
// routes/web.php (Livewire handles most)

Route::middleware(['auth'])->prefix('travel')->group(function () {
    // Trip CRUD
    Route::get('/', TripIndex::class)->name('travel.index');
    Route::get('/create', TripCreate::class)->name('travel.create');
    Route::get('/templates', TripTemplates::class)->name('travel.templates');
    Route::get('/{trip}', TripDetail::class)->name('travel.show');
    
    // Analytics
    Route::get('/analytics', TravelAnalytics::class)->name('travel.analytics');
    Route::get('/map', TravelMap::class)->name('travel.map');
    Route::get('/calendar', TravelCalendar::class)->name('travel.calendar');
    
    // User travel profile
    Route::get('/profile/{user?}', TravelProfile::class)->name('travel.profile');
    
    // API for AJAX/components
    Route::prefix('api')->group(function () {
        Route::post('/trips/{trip}/extract-itinerary', [TripController::class, 'extractItinerary']);
        Route::get('/country-advisory/{countryCode}', [TravelAdvisoryController::class, 'show']);
        Route::post('/trips/{trip}/sync-calendar', [TripController::class, 'syncCalendar']);
    });
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin/travel')->group(function () {
    Route::get('/advisories', ManageAdvisories::class)->name('admin.travel.advisories');
    Route::get('/compliance', ComplianceDashboard::class)->name('admin.travel.compliance');
});
```

---

## 16. Implementation Phases

### Phase 1: Foundation (Week 1-2)
- [ ] Database migrations for all tables
- [ ] TravelProfile model with encryption
- [ ] User profile integration (Travel Profile tab)
- [ ] Basic Trip CRUD (create, read, update, delete)
- [ ] Trip list view with filters
- [ ] Trip detail view (Overview tab only)

### Phase 2: Itinerary Management (Week 2-3)
- [ ] Trip segments (flights) CRUD
- [ ] Trip lodging CRUD
- [ ] Ground transport CRUD
- [ ] Timeline view component
- [ ] Trip destinations for multi-leg trips
- [ ] Conflict detection

### Phase 3: AI Features (Week 3-4)
- [ ] Itinerary extraction from text
- [ ] Itinerary extraction from PDF
- [ ] Review/confirmation UI for extracted data
- [ ] AI-powered checklist suggestions
- [ ] Post-trip debrief prompts

### Phase 4: Financial Tracking (Week 4-5)
- [ ] Expense entry and management
- [ ] Expense categories and summaries
- [ ] Reimbursement workflow (internal)
- [ ] Sponsorship tracking
- [ ] Billing information (management only)
- [ ] Budget vs. actual reporting

### Phase 5: Policy & Compliance (Week 5-6)
- [ ] Country advisory data seeding
- [ ] Automatic risk assessment
- [ ] Compliance checklist generation
- [ ] STEP/insurance requirement flags
- [ ] Prohibited destination warnings
- [ ] Compliance dashboard for admins

### Phase 6: Calendar & Notifications (Week 6-7)
- [ ] Google Calendar event creation
- [ ] Travel block events
- [ ] Flight/lodging events
- [ ] Out-of-office visibility in Team Hub
- [ ] Dashboard widget

### Phase 7: Analytics & Map (Week 7-8)
- [ ] Travel statistics calculations
- [ ] Analytics dashboard
- [ ] Interactive map with Leaflet/Mapbox
- [ ] Project/grant travel reports
- [ ] Export capabilities

### Phase 8: Polish & Templates (Week 8)
- [ ] Trip templates system
- [ ] Duplicate trip functionality
- [ ] Document management
- [ ] Mobile responsiveness
- [ ] Performance optimization
- [ ] Testing and bug fixes

---

## Appendix A: Country Advisory Seeding

```php
// database/seeders/CountryAdvisorySeeder.php

// Level 4 - Do Not Travel
$level4 = ['AF', 'BY', 'MM', 'CF', 'CN', 'CU', 'ET', 'HT', 'IR', 'IQ', 'KP', 'LB', 'LY', 'ML', 'NI', 'RU', 'SO', 'SS', 'SD', 'SY', 'UA', 'VE', 'YE'];

// Prohibited (per POPVOX policy)
$prohibited = ['RU', 'CN', 'IR', 'KP'];

// Level 3 - Reconsider Travel
$level3 = ['DZ', 'BD', 'BF', 'BI', 'CM', 'TD', 'CO', 'CD', 'GQ', 'ER', 'HN', 'KE', 'MR', 'MX', 'MZ', 'NE', 'NG', 'PK', 'PH', 'SN', 'TN', 'TR', 'UG'];

// Level 2 - Exercise Increased Caution (large list, sample)
$level2 = ['AR', 'AZ', 'BS', 'BZ', 'BA', 'BR', 'CL', 'DO', 'EC', 'EG', 'SV', 'GE', 'GT', 'GY', 'IN', 'ID', 'IL', 'JM', 'JO', 'KZ', 'MY', 'MA', 'NP', 'PA', 'PE', 'ZA', 'LK', 'TH', 'TT', 'AE', 'TZ'];

// Level 1 - Everything else (generally safe)
```

---

## Appendix B: Sample AI Prompts

### Itinerary Extraction Prompt
```
You are a travel itinerary parser. Extract structured travel information from the following content.

IMPORTANT:
- Parse ALL flights, hotels, and ground transportation
- Use ISO datetime format (YYYY-MM-DDTHH:MM:SS)
- Use 3-letter airport codes when possible
- Include confidence scores (0.0-1.0) for each extracted field
- If a field is uncertain, set confidence below 0.7
- If a field is missing, omit it (don't guess)

Return valid JSON matching this schema:
{
  "flights": [{
    "carrier": string,
    "carrier_code": string (2 letters),
    "flight_number": string,
    "departure_airport": string (3 letters),
    "departure_datetime": ISO datetime,
    "arrival_airport": string (3 letters),
    "arrival_datetime": ISO datetime,
    "confirmation_number": string,
    "seat": string,
    "cabin_class": "economy"|"premium_economy"|"business"|"first",
    "cost": number,
    "confidence": number
  }],
  "lodging": [{
    "property_name": string,
    "chain": string,
    "city": string,
    "country_code": string (2 letters),
    "check_in_date": ISO date,
    "check_out_date": ISO date,
    "confirmation_number": string,
    "nightly_rate": number,
    "total_cost": number,
    "confidence": number
  }],
  "ground_transport": [{
    "type": "rental_car"|"taxi"|"rideshare"|"shuttle"|"train",
    "provider": string,
    "pickup_datetime": ISO datetime,
    "pickup_location": string,
    "return_datetime": ISO datetime,
    "confirmation_number": string,
    "cost": number,
    "confidence": number
  }]
}

--- CONTENT TO PARSE ---
{content}
--- END CONTENT ---
```

### Checklist Generation Prompt
```
Generate a trip preparation checklist for a nonprofit professional.

Trip Details:
- Destination: {city}, {country}
- Trip Type: {type}
- Duration: {days} days
- Purpose: {description}
- Events: {events}
- Traveler presenting/speaking: {is_presenting}

Travel Policy Notes:
- Advisory Level: {advisory_level}
- STEP Registration Required: {step_required}
- Travel Insurance Required: {insurance_required}

Generate a practical checklist organized by category:
1. Documents & Compliance
2. Electronics & Tech
3. Presentation Materials (if applicable)
4. Health & Safety
5. Clothing & Personal
6. Organization Materials

Consider:
- Destination-specific needs (power adapters, weather, customs)
- Trip type requirements (conference badge, business cards)
- Policy compliance items (STEP, insurance, notifications)
- Professional materials (one-pagers, swag, reports)

Return JSON array:
[{
  "item": string,
  "category": string,
  "priority": "required"|"recommended"|"optional",
  "notes": string (optional context)
}]
```

---

## Appendix C: Map Configuration

```javascript
// resources/js/travel-map.js

import L from 'leaflet';

const initTravelMap = (trips) => {
    const map = L.map('travel-map').setView([20, 0], 2);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Color coding by trip type
    const typeColors = {
        'conference_event': '#3B82F6',      // blue
        'funder_meeting': '#10B981',         // green
        'site_visit': '#F59E0B',             // yellow
        'advocacy_hill_day': '#8B5CF6',      // purple
        'training': '#F97316',               // orange
        'partner_delegation': '#EC4899',     // pink
        'other': '#6B7280'                   // gray
    };
    
    trips.forEach(trip => {
        trip.destinations.forEach(dest => {
            if (dest.latitude && dest.longitude) {
                const marker = L.circleMarker([dest.latitude, dest.longitude], {
                    radius: 8 + (trip.trip_count * 2), // Size by frequency
                    fillColor: typeColors[trip.type] || typeColors.other,
                    fillOpacity: 0.7,
                    stroke: true,
                    color: '#fff',
                    weight: 2
                }).addTo(map);
                
                marker.bindPopup(`
                    <strong>${dest.city}, ${dest.country}</strong><br>
                    ${trip.name}<br>
                    ${trip.start_date} - ${trip.end_date}
                `);
            }
        });
    });
    
    // Draw lines for multi-leg trips
    trips.filter(t => t.destinations.length > 1).forEach(trip => {
        const coords = trip.destinations
            .filter(d => d.latitude && d.longitude)
            .map(d => [d.latitude, d.longitude]);
        
        if (coords.length > 1) {
            L.polyline(coords, {
                color: typeColors[trip.type] || typeColors.other,
                weight: 2,
                opacity: 0.6,
                dashArray: '5, 10'
            }).addTo(map);
        }
    });
};

export { initTravelMap };
```

---

## Appendix D: File Structure

```
app/
├── Models/
│   ├── TravelProfile.php
│   ├── Trip.php
│   ├── TripTraveler.php
│   ├── TripDestination.php
│   ├── TripSegment.php
│   ├── TripLodging.php
│   ├── TripGroundTransport.php
│   ├── TripExpense.php
│   ├── TripSponsorship.php
│   ├── TripEvent.php
│   ├── TripDocument.php
│   ├── TripChecklist.php
│   └── CountryTravelAdvisory.php
│
├── Livewire/Travel/
│   ├── TripIndex.php
│   ├── TripDetail.php
│   ├── TripCreate.php
│   ├── TravelProfile.php
│   ├── TravelAnalytics.php
│   ├── TravelMap.php
│   └── Components/*.php
│
├── Services/
│   ├── TravelItineraryExtractor.php
│   ├── TravelComplianceService.php
│   ├── TravelCalendarService.php
│   └── TravelAnalyticsService.php
│
├── Jobs/
│   ├── ExtractItineraryFromDocument.php
│   ├── CreateTripCalendarEvents.php
│   ├── GenerateTripChecklist.php
│   └── SyncTravelAdvisories.php
│
└── Http/Controllers/
    └── TripController.php

database/migrations/
├── xxxx_create_travel_profiles_table.php
├── xxxx_create_trips_table.php
├── xxxx_create_trip_travelers_table.php
├── xxxx_create_trip_destinations_table.php
├── xxxx_create_trip_segments_table.php
├── xxxx_create_trip_lodging_table.php
├── xxxx_create_trip_ground_transport_table.php
├── xxxx_create_trip_expenses_table.php
├── xxxx_create_trip_sponsorships_table.php
├── xxxx_create_trip_events_table.php
├── xxxx_create_trip_documents_table.php
├── xxxx_create_trip_checklists_table.php
└── xxxx_create_country_travel_advisories_table.php

resources/views/livewire/travel/
├── trip-index.blade.php
├── trip-detail.blade.php
├── trip-create.blade.php
├── travel-profile.blade.php
├── travel-analytics.blade.php
├── travel-map.blade.php
└── components/*.blade.php

resources/js/
└── travel-map.js
```

---

*This specification provides a comprehensive blueprint for implementing the Travel Module in POPVOX WRK. The phased approach allows for iterative development while the detailed schemas and UI mockups provide clear implementation guidance.*
