function sceneTimesPrayTime(options)
{


	function directionQibla(lat, lon) 
	{
		var PI = Math.PI;
		
		if (isNaN(lat-0.0) || isNaN(lon-0.0)) 
		{
			alert("Non-numeric entry/entries");
			return "???";
		}
		
		if ((lat-0.0)>(90.0-0.0) || (lat-0.0)<(-90.0-0.0)) 
		{
			alert("Latitude must be between -90 and 90 degrees");
			return "???";
		}
		
		if ((lon-0.0)>(180.0-0.0) || (lon-0.0)<(-180.0-0.0)) 
		{
			alert("Longitude must be between -180 and 180 degrees");
			return "???";
		}
		
		if (Math.abs(lat-21.4)<Math.abs(0.0-0.01) && Math.abs(lon-39.8)<Math.abs(0.0-0.01)) return "Any";	//Mecca
		
		phiK = 21.4*PI/180.0;
		lambdaK = 39.8*PI/180.0;
		phi = lat*PI/180.0;
		lambda = lon*PI/180.0;
		psi = 180.0/PI*Math.atan2(Math.sin(lambdaK-lambda), Math.cos(phi)*Math.tan(phiK)-Math.sin(phi)*Math.cos(lambdaK-lambda));
		return Math.round(psi);
	}

	var TScene = Solution.Scene.extend({

		
		events: {
		},

		initialize: function(options)
		{
			TScene.__super__.initialize.call(this, options);
			//Solution.navigate('praytime', 'locations');
			this.Position = { latitude: 21.4225, longitude: 39.8262 };
			this.DateTime = 0;
			setInterval(_.bind(Solution.Debug ? this.generatePosition : this.getPosition, this), 10*1000);
		},

		generatePosition: function()
		{
			this.setPosition({coords: {latitude: this.Position.latitude - 0.004, longitude: this.Position.longitude - 0.004}});
			this.onError({ code:0, message:'test' });
		},

		getPosition: function()
		{	
			Solution.Debug && log('getting geo location...');
            if(navigator.geolocation)
                navigator.geolocation.getCurrentPosition(this.setPosition, this.onError);
            else
                this.onError({ code:0, message:'navigator.geolocation is not available' });
			
		},
		
		setPosition: function(position)
		{
			that.$('p.error').fadeOut(600);
			
			that.$('.date').html(that.templates['date']({date: date('D, M d, Y'), time: date('h:i a')}));
			
			var dt = new Date();
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
			
			// if time and position not changed return
			var Dt = dt - that.DateTime;
			var Dp = Math.sqrt( Math.pow(that.Position.latitude-lat, 2) + Math.pow(that.Position.longitude-lng, 2) );
			if( Dt<60000 && Dp<0.005 ) return;
			
			var tz = -1 * dt.getTimezoneOffset()/60;

			prayTimes.tune({imsak: 4, sunrise:-7, dhuhr: 7, asr: 6, sunset: 9, isha: 4});
			
			var times = prayTimes.getTimes(dt, [lat, lng], tz, 'auto', '12h');
			times['direction'] = directionQibla(lat, lng);
			times['latitude'] = lat;
			times['longitude'] = lng;
			
			that.get('/api/geolocation', {latitude: lat, longitude: lng}).done(function(data){
				that.$('.location').html(that.templates['location'](data[0]));
			});			
			//that.$('.date').html(that.templates['date']({date: date('D, M d, Y'), time: date('h:i a')}));
			that.$('.times').html(that.templates['times'](times));	
			that.Position = {latitude: lat, longitude: lng};
			that.DateTime = dt;
		},
		
		onError: function(error)
		{
			switch(error.code)
			{
				case error.PERMISSION_DENIED:
					var s = 'User denied the request for Geolocation.';
					break;
				case error.POSITION_UNAVAILABLE:
					var s = 'Location information is unavailable.';
					break;
				case error.TIMEOUT:
					var s = 'The request to get user location timed out.';
					break;
				case error.UNKNOWN_ERROR:
				default:
					var s = 'An unknown error occurred.';
					break;
			};
			
			that.$('p.error').html(s).fadeIn(200); //.delay(1000).fadeOut(600);
		},
		
		show: function(params)
		{
			this.getPosition();
		}
				
	});

	var that = new TScene(options);
	return that;
}

function sceneQiblaPrayTime(options)
{

	var Map = null;
	var Kabe;
	var Line;
	var Location;
	
	var TScene = Solution.Scene.extend({

		events: {
			'click a.reset': 'resetMap'
		},

		initialize: function(options)
		{
			TScene.__super__.initialize.call(this, options);
		},

		initMap: function(lat, lng)
		{
			Map = new google.maps.Map(
				this.$('article')[0],
				{
					zoom: 18,
					center: new google.maps.LatLng(lat, lng),
					mapTypeId: google.maps.MapTypeId.ROADMAP
				}
			);
			
			Kabe = new google.maps.LatLng(21.4225, 39.8262);

			Line = new google.maps.Polyline({
			    path: [new google.maps.LatLng(lat, lng), Kabe],
			    geodesic: true,
			    strokeColor: '#FF0000',
			    strokeOpacity: 1.0,
			    strokeWeight: 2
			});

			Line.setMap(Map);
			
			google.maps.event.addListener(Map, 'center_changed', function(){
				Line.setPath([Map.getCenter(), Kabe]);
			});
		},
				
		resetMap: function()
		{
			Map.setCenter(Location);
			Map.setZoom(18);
			Map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
			return false;
		},		
				
		show: function(params)
		{
			Location = new google.maps.LatLng(params.lat, params.lon);
			Map ? this.resetMap() : this.initMap(params.lat, params.lon);
		}
	});

	var that = new TScene(options);
	return that;
}

function appPrayTime(options)
{
	var options = _.extend({
		template: '%HTML%'
	}, options);
	
	var TApplication = Solution.Application.extend({

		initialize: function(options){
			TApplication.__super__.initialize.call(this, options);
			this.register(sceneTimesPrayTime({id: 'times-'+this.id}));
			this.register(sceneQiblaPrayTime({id: 'qibla-'+this.id}));
		}
	})
	
	var that = new TApplication(options);
	return that;
}

Solution.register({
	id: 'praytime',
	type: 'app',
	builder: appPrayTime
});