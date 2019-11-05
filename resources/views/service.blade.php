<section id="services">
    <div class="container">
    @include('service-info')    

      <div class="text-center our-services">

        <div class="contact-form">
          <div class="row">
            <div class="col-sm-6">
              <form id="search-form" name="contact-form" method="post" action="/search">
              <div class="form_status" id="form_status" style="display: block;"></div>
                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      <input type="text" id="keyword" name="keyword" class="form-control" style="border-color: green" placeholder="Location" required="required">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                    <button type="submit" class="btn-submit" style="margin-top: 0px">Search Now</button>
                    <input type="hidden" name="_token" value="{{csrf_token()}}">
                    </div>
                  </div>
                </div>                
              </form>   
            </div>           
          </div>
        </div>

        <table id="tides-current-list" class="table table-striped table-bordered" style="width:100%; text-align: left">
          <thead>
              <tr>
                <th>Location</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Code</th>
              </tr>
          </thead>
          <tbody id="tides-current-item">
            @foreach($locations as $key => $value)
              <tr>
                <td><a href="/location/{{ $value->id }}" target="_blank">{{ $value->name }}</a></td>
                <td>{{ $value->latitude }}</td>
                <td>{{ $value->longitude }}</td>
                <td>{{ $value->code }}</td>          
              </tr>
            @endforeach    
          </tbody>
          <tfoot>
          <tr>
                <th>Location</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Code</th>
              </tr>
          </tfoot>
        </table>
        
      </div>
    </div>
  </section><!--/#services-->
