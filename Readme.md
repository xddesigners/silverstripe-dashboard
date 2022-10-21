# Silverstripe Dashboard

Add a dashboard interface to the Silverstripe CMS.

## Installation

```bash
composer require xddesigners/dashboard
```

## Usage

As Default Admin you're able to configure multiple dashboards in the CMS. Each dashboard is restrictable to one or more Groups. The user can then view the dashboards that apply to the user.

A dashboard panel get's his data from a connected Report. So first you'll have to create reports for the different data outputs that you'll want to see reflected in the dashboard.

For example an report that shows recently submitted UserForms. Or one that shows recent Silvershop sales.

When creating a panel you're able to set the defined report filter params so you can have a subset of the report viewable in the dashboard panel.
