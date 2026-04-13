// Novafarma simple frontend helpers
document.addEventListener('DOMContentLoaded', function(){
  // mobile menu toggle
  var toggles = document.querySelectorAll('[data-toggle="menu"]');
  toggles.forEach(function(t){t.addEventListener('click',function(e){e.preventDefault();document.body.classList.toggle('menu-open');});});

  // simple fetch helper wrapper
  window.NovaFetch = async function(url, opts){
    opts = opts || {};
    opts.headers = Object.assign({'Accept':'application/json'}, opts.headers || {});
    if(opts.body && !(opts.body instanceof FormData)){
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.body);
    }
    var res = await fetch(url, opts);
    if(!res.ok){
      var txt = await res.text(); throw new Error('HTTP '+res.status+': '+txt);
    }
    var contentType = res.headers.get('content-type')||'';
    if(contentType.indexOf('application/json')!==-1) return res.json();
    return res.text();
  };
});
