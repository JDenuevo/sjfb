<script>
function initFreshdesk() {
  window.fdWidget.init({
    token: "01KKX5F9JVKJ8KN52EPV7QPC18",
    host: "https://fishbrokers-help.freshdesk.com",
    widgetId: "01KKX5FC8D8NBV1QDCVZ95C7QJ"
  });
}

function initialize(i,t){var e;i.getElementById(t)?initFreshdesk():((e=i.createElement("script")).id=t,e.async=!0,e.src="https://fishbrokers-help.freshdesk.com/webchat/js/widget.js",e.onload=initFreshdesk,i.head.appendChild(e))}function initiateCall(){initialize(document,"Freshdesk-js-sdk")}window.addEventListener?window.addEventListener("load",initiateCall,!1):window.attachEvent("load",initiateCall,!1);
</script>