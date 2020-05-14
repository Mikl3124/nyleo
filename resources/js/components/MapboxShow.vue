<template>
    <div>
      <input type="text" placeholder="Search" v-model="query">        
      <ul v-if="results.length > 0 && query">
        <li v-for="result in results.slice(0,10)" :key="result.id">
          <a :href="result.url">
            <div v-text="result.title"></div>
          </a>
        </li>
      </ul>
    </div>
</template>


<script>
  var mapboxAccessKey = "pk.eyJ1IjoibWlrbDMxMjQiLCJhIjoiY2p5azFtbHQwMDkzZjNlb3J2MHQzcG9pdyJ9.kpmULW-SrFK4XiFFqEmITg";
  console.log(mapboxAccessKey);
    mapboxgl.accessToken = mapboxAccessKey ;
    var mapboxClient = mapboxSdk({ accessToken: mapboxgl.accessToken });
    mapboxClient.geocoding.forwardGeocode({
        query: "18 rue de rigals 09100 saint jean du falga",
        autocomplete: false,
        limit: 1
    })
        .send()
        .then(function (response) {
            if (response && response.body && response.body.features && response.body.features.length) {
                var feature = response.body.features[0];
                var map = new mapboxgl.Map({
                    container: 'map',
                    style: 'mapbox://styles/mapbox/streets-v11',
                    center: feature.center,
                    zoom: 16
                });
                new mapboxgl.Marker()
                    .setLngLat(feature.center)
                    .addTo(map);
            }
        });
</script>
