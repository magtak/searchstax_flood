# SearchStax Flood Protection

This module provides a lightweight rate-limiting layer for Search API servers utilizing the **SearchStax** connector. It prevents excessive Solr queries (select) and index operations (update) by leveraging the core Drupal Flood API.

## Features

* **Configurable Limits:** Installs 4 default flood-related configurations to manage request limits and time windows.
* **No UI:** To keep the module lightweight and developer-focused, all configurations are managed via Drush or the Drupal configuration system.
* **Targeted Protection:** Automatically detects and applies limits only to Search API servers explicitly using the its connector ID.

## Configuration

The module settings are stored in `searchstax_flood.settings`. Since there is no Administrative UI, use Drush to manage these values.

### View Current Settings
`drush cget searchstax_flood.settings`

### Edit Settings
Example: Setting the search limit to 100 requests per window:
`drush cset searchstax_flood.settings select_limit 100`

### Available Configuration Keys

| Key | Default | Description |
| :--- | :--- | :--- |
| **select_limit** | 50 | Number of search queries allowed per window. |
| **select_window** | 10 | Time window (in seconds) for search queries. |
| **update_limit** | 50 | Number of indexing operations allowed per window. |
| **update_window** | 60 | Time window (in seconds) for indexing operations. |

---

## Technical Overview

This module utilizes the **[Drupal Flood API](https://www.drupal.org/docs/8/api/flood-api)** to track and enforce limits. 

Events are registered against the user's IP address. If the defined limit is exceeded within the specified window, the module throws a `SearchApiException`, which halts the query before it is transmitted to the SearchStax Solr endpoint.`
