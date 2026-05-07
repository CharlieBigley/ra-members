# RA Members

This repository contains the Joomla packages used to manage Ramblers membership data.

It currently includes:

- `com_ra_members`: the main Joomla component for managing members, organisations, roles, and reports.
- `plg_ra_members`: a Joomla console plugin that loads users from active organisations.

## Repository Layout

```text
com_ra_members/
  administrator/
  site/
  ra_members.xml
  script.php

plg_ra_members/
  services/
  src/
  ra_members.php
  ra_members.xml
```

The component folder also contains generated release zip files such as `com_ra_members-1.0.7.zip`.

## Requirements

- Joomla 5.x
- A MySQL-compatible database
- `com_ra_tools` installed, since parts of this code use shared helpers, dashboard links, and assets from that component
- `com_ra_mailman` installed, since parts of this code use shared helpers, and dashboard links from that component
## What The Component Provides

`com_ra_members` provides the main administration interface and site code for:

- members
- organisations
- roles
- reports

The component installs database schema from `administrator/sql/install.mysql.utf8.sql` and applies updates from `administrator/sql/updates/`.

## Console Import Plugin

`plg_ra_members` registers the Joomla console command:

```bash
{php-runtime} {website_base}/cli/joomla.php ra_members:loadusers
```

That command loads users from active organisations and writes progress to the RA log table.

## Installation

Typical installation flow:

1. Install or update `com_ra_tools` first.
2. Install `com_ra_members`.
3. Install `plg_ra_members`.
4. Activate the plug-in within Joomla
5. Set the 'mailman-active' flag on the required organisations 
6. Create an entry in Api-sites for each organisation, and include the matching 
API access key
7. Create a "scheduled task" (aka a cron job) to run the membership import as required - prabably each night
8. Use the back-end Joomla administrator area set fine-tune the requirements
9. The front-end Joomla site is then operational

If you are packaging releases manually, install from the generated zip archive for the component, or create fresh package archives from the source tree before deployment.

## Development Notes

- Main component manifest: `com_ra_members/administrator/ra_members.xml`
- Plugin manifest: `plg_ra_members/ra_members.xml`
- Console command: `{php-runtime} {website_base}/cli/joomla.php ra_members:loadusers`
e.g /usr/bin/php83  /home/sites/4a/3/376204d07d/public_html/mailman/cli/joomla.php  ra_members:loadusers
- This repository uses GitHub at `CharlieBigley/ra-members`

## Git

Local-only files are excluded by the root `.gitignore`, including editor settings, dependency directories, logs, and NetBeans private project state.