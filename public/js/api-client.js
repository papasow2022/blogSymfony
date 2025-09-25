// API client moved from public/api-client.js to public/js/api-client.js
// If you reference this file, update your script src accordingly.

// Temporary shim: load the original file if it still exists
(function(){
  var old = '/api-client.js';
  var s = document.createElement('script');
  s.src = old;
  document.currentScript && document.currentScript.parentNode.insertBefore(s, document.currentScript);
})();