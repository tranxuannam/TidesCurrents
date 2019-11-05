<!DOCTYPE html>
<html lang="en">
@include('head')
<body>
  @include('header')
  <section id="location-detail">
    <div class="container">
      <h3>{{$location->name}}</h3>
      Code: {{$location->code}} <br />
      <div id="google-map" class="wow fadeIn" data-latitude="{{$location->latitude}}" data-longitude="{{$location->longitude}}" data-wow-duration="1000ms" data-wow-delay="400ms"></div>
      <br />
      <small>
        <a href="https://maps.google.com/maps?q={{$location->latitude}},{{$location->longitude}}&hl=en;z=8&output=embed" target="_blank" > See map bigger </a>
      </small>
    </div>
  </section><!--/#location-detail-->  
  @include('footer')
</body>
</html>