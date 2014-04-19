
var Solution = {
	
	id: 'solution',
	version: '5.0.0',
	App: {},
	Module: {},
	Resource: {},
	User: {'id':0},
	Root: '',
	ApiServer: '',
	Debug: false,
	
	initialize: function(options)
	{	
		_.extend({debug: false}, options||{});
	
		this.Debug = options.debug;
	
		Solution.Router = new TRouter;
		Backbone.history.start({pushState: true});
	
		$(document).on('click', 'a', function()
		{
			Solution.Router.navigate($(this).attr('href'), {trigger: true});
			return false;
		})
	},

	load: function(objectId, callback, times)
	{
		times = times?times:0;
		if(times > 3)
		{
			log('requested url not found! ' + objectId);
			return;	
		} 
		var ns = objectId.split('-');
		
		var url = '/' + ns[1]+'/'+ns[0]+'.js'; 
		this.Debug && log('load: ' + url + ', times: ' + times);
		Modernizr.load({
			test: typeof(this.Resource[objectId]) !== 'undefined',
			nope: url,
			complete: _.bind(function(){
				this.Debug && log('load complete: ' + objectId + ' === ' + typeof(this.Resource[objectId]));	
				if( typeof(this.Resource[objectId]) !== 'object' )
				{
					this.Debug && log('waiting for ' + objectId);
					setTimeout( function(a, b, c){Solution.load(a, b, c);}, 100, objectId, callback, ++times );
				} 
				else
					if(typeof(this.Resource[objectId]) === 'object' & typeof(callback) === 'function'){
						callback.call(this, this.Resource[objectId]);
					}
			}, this)	
		});
	},		

	navigate: function(app, scene, params)
	{	
		/*
			navigate ( this ) function runs only for applications.
			we care another function to load module and reports
			
			All apps must have only one instance. When user need to another copy, please right click and open in new tab :-) 
		*/
		this.load(app+'-app', function(obj){
			var application = obj.create('#container');
			application.show(scene, params);
		})
	},

	register: function(options)
	{
		/*
			Uset this function to Register Resources
			min requirement: 
				id: object name
				type: app, module, report
				builder: function reference to create an instance
		*/
		this.Debug && log('register: ' + options.id + '-' + options.type);
		var app = _.extend({
			objectId: options.id + '-' + options.type,
			instance: [],
			builder: null,
			create: function(container, options){
				if( this.instance.length == 0 )
				{
					options = _.extend({
						id: this.id+'-'+this.type,
						container: container
					}, options||{});
			
					this.instance[0] = this.builder(options);
					//$(container).append(this.instance[0].$el);
				}
				return this.instance[0];
			}
		}, options)

		if( !(app.objectId in this.Resource) ) this.Resource[app.objectId] = app;
	},
	
	setUser: function(user)
	{
		this.User = user;
		this.trigger(user.id>0 ? 'login' : 'logout');
	},
	
	logout: function(url)
	{
		this.getJSON('/api/auth/logout', _.bind(function(user){
			this.setUser(user);
		}, this));
	},
	
	trigger: function(event, data)
	{
		_.each(this.Resource, function(res){
			_.each(res.instance, function(obj){ 
				//log('send '+event+' event for ' + obj.id);
				obj.$el.trigger(event);
				if(obj.type='app')
					_.each(obj.Scene, function(scene){
						//log('send '+event+' event for ' + scene.id);
						scene.$el.trigger(event);
					})
				
			})
		})
		
	},
	
	ajax: function(url, options)
	{
		var ajax = $.ajax( 
			url, 
			_.extend({dataType: 'json', type: 'GET'}, options) 
		)
		.fail(
			function(xhr, textStatus, errorThrown){
				console.log( 'getJSON Failed: ' + xhr.status + ', ' + xhr.statusText + ', ' + textStatus);
				switch(xhr.status)
				{
					case 401:
						Solution.navigate('auth', 'login');	
						break;
					default:
						if(textStatus=='parsererror')
							alert(xhr.responseText)
						else
							alert('Unexceptable error ( ' + xhr.status + ', ' + xhr.statusText + ', ' + textStatus + ")\n" + xhr.responseText);
				}
				
			}
		);
	
		return ajax;
	},
	
	get: function(url, data)
	{
		return this.ajax(url, {data: data, type: 'GET'});
	},
	
	post: function(url, data)
	{
		return this.ajax(url, {data: data, type: 'POST'});
	},
	
	showError: function(ecode, obj)
	{
		switch(obj.status)
		{
			case 401:
				Solution.navigate('auth', 'login');
				break;
			default:
				alert( obj.responseText ); //+ "\n\n(" + obj.status + ', ' + obj.statusText + ')' );
		}
		
	}

}

/* Model */
Solution.Model = Backbone.Model.extend({

	initialize: function()
	{
		this.bind('error', Solution.showError);
	},
	
	getJSON: function(url, data, callback)
	{
		Solution.getJSON(url, data, callback);	
	}
	
});

/* View */
Solution.View = Backbone.View.extend({
	
});

/* Collection */
Solution.Collection = Backbone.Collection.extend({
	initialize: function(){
		this.filter = null; 
		//this.bind('error', _.bind(Solution.showError, Solution));
	},
	
	fetch: function(options)
	{
		options = options||{};
		if( this.filter )
		{
			if( 'data' in options ) 
				_.extend(options['data'], this.filter);
			else	
				options['data'] = this.filter;
		}
		_.extend(options, {error: Solution.showError});
		Solution.Collection.__super__.fetch.call(this, options);
	}
});

/* Module */
Solution.Module = Backbone.View.extend({

	tagName: 'div',
	className: 'module',
	initialize: function(options)
	{
		Solution.Debug && log('initialize: ' + this.id);
		this.initialized = {};
		this.templates = {};
		//log(typeof options['template']);
		if(typeof options['template'] === 'string'){
			var html = options['template'].replace(/(%ID%)/g, this.id);
			this.$el.html( html );
			// template scriptleri bul, create et, ve template'i sil.
			this.$el.children('script[type="text/template"]').each(_.bind(function(index, script){
				//log('template: '+ $(script).attr('id'));
				var id = $(script).attr('id');
				this.templates[id] = _.template( $(script).html() );
				$(script).remove();
			}, this));
			
			this.$el.children().each(function(){
				var name = $(this).attr('id');
				$(this).attr('id', name+'-'+options.id);
			});
			
			$(options['container']).append(this.$el);

		}
	},

	get: function(url, data)
	{
		return Solution.ajax(url, {data: data, type: 'GET'});
	},

	post: function(url, data)
	{
		return Solution.ajax(url, {data: data, type: 'POST'});
	},

	$find: function(id)
	{
		return this.$el.find('#'+id+'-'+this.id);
	}
});

/* Application	*/
Solution.Application = Solution.Module.extend({

	tagName: 'section',
	className: 'application',

		
	initialize: function(options){
		this.Scene = {};
		Solution.Application.__super__.initialize.call(this, options);
	},

	register: function(scene)
	{
		var id = scene.id.split('-'+this.id)[0];
		this.Scene[id] = scene;
		
	},

	$find: function(id)
	{
		return this.$el.find('#'+id+'-'+this.id);
	},
	
	show: function(scene, params)
	{
	
		Solution.Debug && log('show: ' + this.id);
		// if scene not defined select first scene
		if(!scene) scene = _.keys(this.Scene)[0];
		// if scene not created throw exception
		if( !(scene in this.Scene)) throw 'Scene '+scene+'-'+this.id+' not found!';

		var $scene = this.Scene[scene];
		// show requested scene, others hide	
		$scene.$el.show().siblings('section').hide();
		// show current app, orhers hide
		this.$el.show().siblings('section').hide();
		
		$scene.show(params);
	}
});

/* Scene */
Solution.Scene = Solution.Module.extend({

	tagName: 'section',
	className: 'scene',
		
	initialize: function(options){
		this.templates = {};
		this.setElement($('#'+this.id));
		this.$el.children('script[type="text/template"]').each(_.bind(function(index, script){
			//log('template: '+ $(script).attr('id'));
			var id = $(script).attr('id');
			this.templates[id] = _.template( $(script).html() );
			$(script).remove();
		}, this));
		
		this.$el.find('[id]').each(function(i, el){
			var name = $(this).attr('id');
			$(this).attr('id', name+'-'+options.id);
		});
		
	},

	$find: function(id)
	{
		return this.$el.find('#'+id+'-'+this.id);
	},

	show: function(params){
		Solution.Debug && log('show: ' + this.id);
	}
});

/* Router */
var TRouter = Backbone.Router.extend({
	routes : 
	{
		'': 'defaultApplication',
		':app': 'loadApplication',
		':app/:scene': 'loadScene',
		':app/:scene/:param(/:param)(/:param)(/:param)(/:param)': 'loadParams'
	},

	defaultApplication: function()
	{
		Solution.navigate('praytime', 'times');	
	},

	loadApplication : function(name)
	{
		Solution.navigate(name)
	},

	loadScene : function(name, scene)
	{
		Solution.navigate(name, scene);
	},
	
	loadParams: function(name, scene)
	{
		var params = {};
		_.each(_.compact(arguments).slice(2), function(param){
			var t = param.split('='); 
			if(t.length>1) params[t[0]]=t[1]; else params.id=param;
		})
		Solution.navigate(name, scene, _.extend({id: 0}, params) );
	}
	
});

window.log = function log(s){ 
	'console' in window && 'log' in console && console.log(s); 
}
