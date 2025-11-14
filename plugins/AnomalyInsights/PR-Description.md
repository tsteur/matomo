## Summary

Matomo Cloud users depend on analytics to make timely decisions about their websites, campaigns, and user behavior. 
Yet, many only notice important changes—like sudden traffic drops or conversion spikes—when they happen to check their dashboards. 
The Anomaly Insights feature aims to close that gap by automatically identifying unusual patterns in key metrics 
(such as visits, conversions, or bounce rate) and surfacing them directly in the dashboard. 
Instead of expecting users to dig through charts, Matomo will proactively highlight “what’s interesting” and “why it happened.”

This MVP includes:

* A background job that periodically analyzes recent site data and writes detected anomalies into a new anomaly_insights table.
* An API endpoint to query anomalies for a given site and date range.
* A new AnomalyPanel.vue component that displays detected anomalies in the UI.
* Supporting configuration and database migration.
* The goal is to give users early visibility into unexpected data trends without requiring manual analysis.

## Testing

* Added unit tests for the detector logic.
* Added an integration test for the new endpoint.
* Verified locally with one test site; anomalies are detected correctly.
* CI passes with no errors (two minor warnings).

## Example API response:
```
[
{
"metric": "visits",
"score": 3.42,
"window_start": "2025-10-10T00:00:00Z",
"window_end": "2025-10-11T00:00:00Z",
"explanation_json": "<b>Visits</b> increased by 120% compared to average."
}
]
```
