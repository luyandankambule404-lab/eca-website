const CACHE_NAME = "eca-cpd-v1";

const urlsToCache = [
  "/cpd/",
  "/cpd/contractor/dashboard.php",
  "/cpd/contractor/courses.php",
  "/cpd/contractor/applications.php",
  "/cpd/assets/css/style.css",
  "/cpd/assets/js/app.js"
];

self.addEventListener("install", event => {

  event.waitUntil(

    caches.open(CACHE_NAME)
    .then(cache => {
      return cache.addAll(urlsToCache);
    })

  );

});

self.addEventListener("fetch", event => {

  event.respondWith(

    caches.match(event.request)
    .then(response => {

      if(response){
        return response;
      }

      return fetch(event.request);

    })

  );

});