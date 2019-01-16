/* idb-keyval v3.1.0 github.com/jakearchibald/idb-keyval */
var idbKeyval=function(e){"use strict";class t{constructor(e="keyval-store",t="keyval"){this.storeName=t,this._dbp=new Promise((r,n)=>{const o=indexedDB.open(e,1);o.onerror=(()=>n(o.error)),o.onsuccess=(()=>r(o.result)),o.onupgradeneeded=(()=>{o.result.createObjectStore(t)})})}
_withIDBStore(e,t){return this._dbp.then(r=>new Promise((n,o)=>{const s=r.transaction(this.storeName,e);s.oncomplete=(()=>n()),s.onabort=s.onerror=(()=>o(s.error)),t(s.objectStore(this.storeName))}))}}let r;function n(){return r||(r=new t),r}return e.Store=t,e.get=function(e,t=n()){let r;return t._withIDBStore("readonly",t=>{r=t.get(e)}).then(()=>r.result)},e.set=function(e,t,r=n()){return r._withIDBStore("readwrite",r=>{r.put(t,e)})},e.del=function(e,t=n()){return t._withIDBStore("readwrite",t=>{t.delete(e)})},e.clear=function(e=n()){return e._withIDBStore("readwrite",e=>{e.clear()})},e.keys=function(e=n()){const t=[];return e._withIDBStore("readonly",e=>{(e.openKeyCursor||e.openCursor).call(e).onsuccess=function(){this.result&&(t.push(this.result.key),this.result.continue())}}).then(()=>t)},e}({});

var CACHE_NAME = 'draw-cache-v1';
var urlsToCache = [
  '../../draw',
  'css/main.css',
  'js/mousetrap.min.js',
  'js/main.js',
  '../../draw/api/v1/lists'
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function (cache) {
        console.log('Opened cache');
        fetchApi('lists').then(lists => {
          lists.lists.forEach(entry => cache.add('../../draw/api/v1/' + entry.list));
        });
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', function (event) {
  event.respondWith(
    caches.match(event.request)
      .then(function (response) {
        if (response) {
          return response;
        }
        var fetchRequest = event.request.clone();

        return fetch(fetchRequest, { credentials: 'include' }).then(
          function (response) {
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            var responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then(function (cache) {
                cache.put(event.request, responseToCache);
              });

            return response;
          }
        );
      })
  );
});

self.addEventListener('sync', function (event) {
  if (event.tag == 'db') {
    event.waitUntil(sync());
  }
});

function fetchApi(site) {
  return new Promise((resolve, reject) => {
      console.log('fetching ' + 'draw/api/v1/' + site);
      fetch('../../draw/api/v1/' + site, {
          credentials: 'include'
      }).then(response => {
          if (response.status >= 200 && response.status < 300) {
            return Promise.resolve(response)
          } else {
            return Promise.reject(response)
          }
        }).then(response => {
          return response.json()
        }).then(json => {
          if (json.code == 0) {
            return Promise.resolve(json);
          } else {
              return Promise.reject(json);
          }
        })
        .then(resolve)
        .catch(reject);
  });
}

function seed(e) {
  return new Promise((resolve, reject) => {
    if (typeof e == 'undefined') {
      console.log('sync: seeding local DB')
      e = []
      idbKeyval.set('draws', e).then(() => resolve(e), b => reject(b));
    } else {
      resolve(e);
    }
  });
}

function draw(entry) {
  return fetchApi(entry.list + '/draw/' + entry.book);
}

function revoke(entry) {
  return fetchApi(entry.list + '/revoke/' + entry.book);
}

function sync() {
  return new Promise((resolve, reject) => {
    console.log('sync: syncing..');
    Promise.all([
      idbKeyval.get('draws').then(seed),
      fetchApi('draws?all').then(e => Object.values(e.draws))
    ]).then(data => {
        let local = data[0].filter(x => (!data[1].find(e => {
          return e.list == x.list
        }) || x.revoke == 1));
        
        console.log('sync: uploading ' + local.length + ' entries');
        local.forEach(entry => {
          if (entry.revoke == 1) {
            revoke(entry);
          } else {
            draw(entry).catch(e => {
              if (e.code != 3) {
                reject(e.message)
              } else {
                console.log('sync: forcing draw')
                revoke(e)
                  .then(() => draw(entry)
                    .catch(e2 => reject(e2.message))
                  , e2 => reject(e2.message));
              }
            })
          }
        });

        if (local.length) {
          fetchApi('draws?all').then(e => {
            let save = Object.values(e.draws);
            console.log('sync: saving ' + save.length + ' entries')
            resolve(idbKeyval.set('draws', save));
          }, e => reject(e.message));
        } else {
          console.log('sync: saving ' + data[1].length + ' entries')
          resolve(idbKeyval.set('draws', data[1]));
        }
      });
  });
}

