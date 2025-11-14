# Anomaly Insights MVP

Lightweight panel that surfaces unusual movement for core site metrics. The current build focuses on covering the happy path so product/design can iterate on the visual direction.

![Happy path](https://static.matomo.org/img/screenshots/anomaly-panel.png)

## API surface

```
GET /api/v1/sites/{id}/anomalies?from=ISO8601&to=ISO8601&metric=visits
```

The endpoint proxies to `AnomalyInsights.getSiteAnomalies` and returns the raw rows persisted in `anomaly_insights`.

## Usage

1. Enable the feature flag (`enable_anomaly_insights=1`) and refresh the dashboard.
2. Drop `<AnomalyPanel site-id="123" />` anywhere inside an insights view to mount the widget.
3. Tune scoring thresholds inside `AnomalyDetector` if you need sharper spikes.
