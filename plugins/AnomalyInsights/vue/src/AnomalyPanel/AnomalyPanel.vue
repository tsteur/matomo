<template>
  <div class="anomaly-panel">
    <div class="anomaly-panel__header">
      <h3>Anomaly Insights MVP</h3>
      <button class="anomaly-panel__refresh" @click="refresh">Refresh</button>
    </div>
    <div v-if="loading" class="anomaly-panel__loading">
      <span class="anomaly-panel__spinner"></span>
    </div>
    <div v-else class="anomaly-panel__list">
      <div
        v-for="insight in insights"
        :key="insight.id + '-' + insight.metric"
        class="anomaly-panel__row"
      >
        <div class="anomaly-panel__sparkline" :style="{ borderColor: insight.color }"></div>
        <div class="anomaly-panel__details">
          <div class="anomaly-panel__metric">{{ insight.metric }}</div>
          <div class="anomaly-panel__score" :style="{ color: insight.color }">
            {{ insight.score.toFixed(2) }}
          </div>
        </div>
        <div class="anomaly-panel__explanation" v-html="insight.explanationHtml"></div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AnomalyPanel',
  props: {
    siteId: {
      type: Number,
      required: true,
    },
    metric: {
      type: String,
      default: 'visits',
    },
  },
  data() {
    return {
      loading: true,
      insights: [],
      unsubscribe: null,
    };
  },
  mounted() {
    this.refresh();
    this.unsubscribe = this.$store.watch(
      () => this.$store.state,
      () => this.refresh(),
      { deep: true }
    );
  },
  beforeDestroy() {
    if (this.unsubscribe) {
      this.unsubscribe();
    }
  },
  methods: {
    refresh() {
      this.loading = true;
      const now = new Date();
      const params = new URLSearchParams({
        module: 'API',
        method: 'AnomalyInsights.getSiteAnomalies',
        idSite: String(this.siteId),
        metric: this.metric,
        from: new Date(now.getTime() - 24 * 60 * 60 * 1000).toISOString(),
        to: now.toISOString(),
        format: 'json',
      });

      fetch(`index.php?${params.toString()}`, { credentials: 'include' })
        .then((response) => response.json())
        .then((payload) => {
          this.insights = (payload.data || []).map((row) => this.decorate(row));
          this.loading = false;
        });
    },
    decorate(row) {
      const explanation = row.explanation || row.explanation_json || {};
      const html =
        typeof explanation === 'string'
          ? explanation
          : `<strong>${explanation.reason || 'Spike detected'}</strong><div>${JSON.stringify(
              explanation
            )}</div>`;

      return {
        ...row,
        explanationHtml: html,
        color: row.score > 0.65 ? '#f44336' : '#4caf50',
      };
    },
  },
};
</script>

<style scoped>
.anomaly-panel {
  border: 1px solid var(--border-color, #d9d9d9);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.anomaly-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.anomaly-panel__refresh {
  font-size: 12px;
  text-transform: uppercase;
}

.anomaly-panel__loading {
  min-height: 60px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.anomaly-panel__spinner {
  width: 20px;
  height: 20px;
  border-radius: 999px;
  border: 2px solid #ccc;
  border-top-color: #2196f3;
  animation: spin 0.8s linear infinite;
}

.anomaly-panel__row {
  display: grid;
  grid-template-columns: 60px 120px 1fr;
  gap: 16px;
  align-items: center;
  padding: 8px 0;
}

.anomaly-panel__sparkline {
  width: 60px;
  height: 26px;
  border: 2px solid #4caf50;
  border-radius: 3px;
}

.anomaly-panel__metric {
  font-weight: 600;
}

.anomaly-panel__score {
  font-size: 18px;
  font-weight: bold;
}

.anomaly-panel__explanation {
  font-size: 12px;
  line-height: 1.4;
}

@keyframes spin {
  from {
    transform: rotate(0);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
