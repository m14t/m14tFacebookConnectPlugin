if ( !window.m14t ) { window.m14t={}; }

m14t.facebook = (function(){
  var win,
      cb;

  return {
    requestAuthorization: function(href) {
      win = window.open(href + "&ajax=true", "m14tPopup", "menubar=0,resizable=1,width=1000,height=650");
    },
    setCallback: function(fn) {
      cb = fn;
    },
    callback: function() {
      if ( "function" == typeof(cb) ) {
        cb();
      }
    }
  };
}());
