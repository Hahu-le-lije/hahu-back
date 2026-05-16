# CMS Database Schema

**Database**: PostgreSQL 15+ | **Host**: 127.0.0.1:5432 | **Database**: cms_db | **User**: cms_user

---

## Overview

```
AUTHENTICATION          CONTENT              INFRASTRUCTURE
├─ users               ├─ content_packs     ├─ cache
├─ parents             ├─ content_pack_     ├─ cache_locks
├─ sessions            │  versions          ├─ jobs
├─ personal_access_    └─ content           ├─ job_batches
│  tokens                                   └─ failed_jobs
└─ password_reset_tokens
```

---

## Tables

### users
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| name | varchar(255) | NOT NULL |
| email | varchar(255) | NOT NULL, UNIQUE |
| password | varchar(255) | NOT NULL |
| role | varchar(255) | DEFAULT 'user' |
| email_verified_at | timestamp | NULL |
| remember_token | varchar(100) | NULL |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Indexes**: `email` (UNIQUE)

---

### parents
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| clerk_id | varchar(255) | NOT NULL, UNIQUE |
| first_name | varchar(255) | NOT NULL |
| last_name | varchar(255) | NOT NULL |
| email | varchar(255) | NOT NULL, UNIQUE |
| phone_number | varchar(255) | NULL |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Indexes**: `clerk_id` (UNIQUE), `email` (UNIQUE)

---

### sessions
| Column | Type | Constraints |
|--------|------|-------------|
| id | varchar(255) | PK |
| user_id | bigint | FK → users.id (nullable) |
| ip_address | varchar(45) | NULL |
| user_agent | text | NULL |
| payload | longtext | NOT NULL |
| last_activity | integer | NOT NULL |

**Indexes**: `user_id`, `last_activity`

---

### personal_access_tokens
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| tokenable_type | varchar(255) | NOT NULL |
| tokenable_id | bigint | NOT NULL |
| name | varchar(255) | NOT NULL |
| token | varchar(64) | NOT NULL, UNIQUE |
| abilities | text | NULL |
| last_used_at | timestamp | NULL |
| expires_at | timestamp | NULL |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Polymorphic**: Can reference users or parents

---

### password_reset_tokens
| Column | Type | Constraints |
|--------|------|-------------|
| email | varchar(255) | PK |
| token | varchar(255) | NOT NULL |
| created_at | timestamp | NULL |

---

### content_packs
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| slug | varchar(255) | NOT NULL, UNIQUE |
| title | varchar(255) | NOT NULL |
| description | text | NULL |
| game_type | varchar(255) | NULL |
| thumbnail_url | varchar(255) | NULL |
| size_mb | bigint | NULL |
| is_active | boolean | DEFAULT true |
| latest_published_version | integer | NULL |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Indexes**: `slug` (UNIQUE), `(is_active, latest_published_version)`

---

### content_pack_versions
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| content_pack_id | bigint | FK → content_packs.id (CASCADE DELETE) |
| version | integer | NOT NULL |
| checksum | varchar(64) | NOT NULL |
| size_bytes | bigint | NOT NULL |
| payload | json | NOT NULL |
| min_app_version | varchar(255) | DEFAULT '1.0.0' |
| published_at | timestamp | NULL |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Constraints**: UNIQUE `(content_pack_id, version)`
**Indexes**: `(content_pack_id, published_at)`

---

### content
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| content_pack_version_id | bigint | FK → content_pack_versions.id (CASCADE DELETE) |
| type | varchar(255) | NOT NULL |
| title | varchar(255) | NOT NULL |
| description | text | NULL |
| content | json | NOT NULL |
| sequence_order | integer | NOT NULL |
| difficulty | varchar(50) | NULL |
| is_active | boolean | DEFAULT true |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Indexes**: `(content_pack_version_id, sequence_order)`, `type`, `difficulty`

---

### child_subjects
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| child_id | varchar(255) | NOT NULL, indexed |
| game_type_id | integer | NOT NULL |
| game_type_name | varchar(255) | NOT NULL |
| status | boolean | DEFAULT true |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Constraints**: UNIQUE(`child_id`, `game_type_id`)

---

### assigned_tasks
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| child_id | varchar(255) | NOT NULL, indexed |
| content_id | bigint | FK → content.id (CASCADE on delete) |
| game_type_id | integer | NOT NULL |
| status | varchar(50) | DEFAULT 'assigned' |
| assigned_by | bigint | nullable, indexed (user id who assigned) |
| assigned_at | timestamp | nullable |
| completed_at | timestamp | nullable |
| reason | text | nullable |
| created_at | timestamp | DEFAULT NOW() |
| updated_at | timestamp | DEFAULT NOW() |

**Indexes**: `child_id`, `assigned_by`


---

### cache
| Column | Type | Constraints |
|--------|------|-------------|
| key | varchar(255) | PK |
| value | mediumtext | NOT NULL |
| expiration | integer | NOT NULL |

**Indexes**: `expiration`

---

### cache_locks
| Column | Type | Constraints |
|--------|------|-------------|
| key | varchar(255) | PK |
| owner | varchar(255) | NOT NULL |
| expiration | integer | NOT NULL |

**Indexes**: `expiration`

---

### jobs
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| queue | varchar(255) | NOT NULL |
| payload | longtext | NOT NULL |
| attempts | tinyint | NOT NULL |
| reserved_at | integer | NULL |
| available_at | integer | NOT NULL |
| created_at | integer | NOT NULL |

**Indexes**: `queue`

---

### job_batches
| Column | Type | Constraints |
|--------|------|-------------|
| id | varchar(255) | PK |
| name | varchar(255) | NOT NULL |
| total_jobs | integer | NOT NULL |
| pending_jobs | integer | NOT NULL |
| failed_jobs | integer | NOT NULL |
| failed_job_ids | longtext | NOT NULL |
| options | mediumtext | NULL |
| cancelled_at | integer | NULL |
| created_at | integer | NOT NULL |
| finished_at | integer | NULL |

---

### failed_jobs
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, AUTO_INCREMENT |
| uuid | varchar(255) | NOT NULL, UNIQUE |
| connection | text | NOT NULL |
| queue | text | NOT NULL |
| payload | longtext | NOT NULL |
| exception | longtext | NOT NULL |
| failed_at | timestamp | DEFAULT NOW() |

---

## Relationships

```
users (1) ──────→ (many) personal_access_tokens
parents (1) ─────→ (many) personal_access_tokens
users (1) ───────→ (many) sessions
content_packs (1) ────→ (many) content_pack_versions
                           ↓
                       (1) content_pack_versions ───→ (many) content
                           └─── CASCADE DELETE on content
```

---

## Key Indexes

| Table | Index | Purpose |
|-------|-------|---------|
| users | email | User login |
| parents | clerk_id, email | External auth |
| content_packs | is_active + latest_published_version | Get active versions |
| content_pack_versions | (content_pack_id, version) | Unique constraint |
| content_pack_versions | (content_pack_id, published_at) | Query versions |
| content | (content_pack_version_id, sequence_order) | Get ordered content |
| content | type, difficulty | Filter by type/difficulty |
| jobs | queue | Queue processing |
| cache, cache_locks | expiration | TTL cleanup |
| sessions | user_id, last_activity | Session cleanup |

---

## Migrations Applied

All 10 migrations have been successfully created:

1. create_users_table (users, password_reset_tokens, sessions)
2. create_cache_table (cache, cache_locks)
3. create_jobs_table (jobs, job_batches, failed_jobs)
4. create_parents_table (parents)
5. update_parents_table_for_clerk (add Clerk fields)
6. create_content_packs_table (content_packs)
7. create_content_pack_versions_table (content_pack_versions)
8. add_role_to_users_table (add role column)
9. create_personal_access_tokens_table (personal_access_tokens)
10. create_content_table (content) ← NEW
11. create_child_subjects_table (child_subjects) ← NEW
12. create_assigned_tasks_table (assigned_tasks) ← NEW

---

## Running Migrations

```bash
# Run pending migrations
php artisan migrate

# Check migration status
php artisan migrate:status
```

---

## Models Created

- `App\Models\Content` - Eloquent model for content table with scopes

**Usage Examples:**
```php
// Get all active content for a version, ordered
$content = Content::active()
    ->where('content_pack_version_id', $versionId)
    ->ordered()
    ->get();

// Filter by type (e.g., 'question', 'lesson')
$questions = Content::ofType('question')->active()->get();

// Filter by difficulty
$hardItems = Content::byDifficulty('hard')->get();

// Get with relationships
$content = Content::with('contentPackVersion')->find($id);
```
