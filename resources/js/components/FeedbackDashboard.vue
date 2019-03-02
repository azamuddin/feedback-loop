<template>
  <div class="container">
    <div class="row mt-5">
      <div class="col-md-4 offset-4 text-left">
        <h1 class="text-left">Top Word</h1>
        <hr>
        <ul style="list-style: none;padding:0">
          <li v-bind:key="index" v-for="(feedback, index) in data">
            <h1 v-if="index == 0">{{feedback.word}} ({{feedback.count}})</h1>
            <h2 v-if="index == 1">{{feedback.word}} ({{feedback.count}})</h2>
            <h3 v-if="index == 2">{{feedback.word}} ({{feedback.count}})</h3>
            <h4 v-if="index > 2">{{feedback.word}} ({{feedback.count}})</h4>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>


<script>
import axios from "axios";

export default {
  data() {
    return {
      data: null,
      status: "IDLE", // FETCHING | IDLE | ERROR
      message: ""
    };
  },
  mounted() {
    this.fetchInitialData();
    this.listenForChange();
  },
  methods: {
    fetchInitialData() {
      axios
        .get("/api/v1/feedback/data")
        .then(response => {
          this.$data.data = response.data;
          this.$data.status = "SUCCESS";
        })
        .catch(error => {
          this.$data.status = "ERROR";
        });
    },
    listenForChange() {
      window.Echo.channel("feedback-received").listen(
        "FeedbackReceived",
        payload => {
          this.$data.data = payload;
        }
      );
    }
  }
};
</script>
