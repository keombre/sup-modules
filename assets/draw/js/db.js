function status(response) {
    if (response.status >= 200 && response.status < 300) {
      return Promise.resolve(response)
    } else {
      return Promise.reject(response)
    }
}

function json(response) {
    return response.json()
}

function apiState(json) {
    if (json.code == 0) {
        return Promise.resolve(json);
    } else {
        return Promise.reject(json);
    }
}

function fetchApi(site) {
    return new Promise((resolve, reject) => {
        console.log('fetching ' + 'draw/api/v1/' + site);
        fetch('draw/api/v1/' + site, {
            credentials: 'include'
        })
            .then(status)
            .then(json)
            .then(apiState)
            .then(resolve)
            .catch(reject);
    });
}

export function draw(entry) {
    return fetchApi(entry.list + '/draw/' + entry.book);
}

export function revoke(entry) {
    return fetchApi(entry.list + '/revoke/' + entry.book);
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

export function sync() {
    console.log('sync: syncing..');
    Promise.all([idbKeyval.get('draws').then(seed), fetchApi('draws?all').then(e => Object.values(e.draws))])
        .then(data => {
            let local = data[0].filter(x => !data[1].find(e => {
                return e.id == x.id
            }));
            
            console.log('sync: uploading ' + local.length + ' entries');
            local.forEach(entry => draw(entry).catch(e => {
                if (e.code != 3) {
                    new Error(e.message)
                } else {
                    console.log('sync: forcing draw')
                    revoke(e)
                        .then(() => draw(entry)
                            .catch(e => new Error(e.message))
                        ).catch(e => new Error(e.message));
                }
            }));

            if (local.length) {
                fetchApi('draws?all').then(e => {
                    let save = Object.values(e.draws);
                    console.log('sync: saving ' + save.length + ' entries')
                    idbKeyval.set('draws', save);
                });
            } else {
                console.log('sync: saving ' + data[1].length + ' entries')
                idbKeyval.set('draws', data[1]);
            }
        });
}

export function init() {
    //draw({'time': 0, 'list': 293638, 'book': 1})
    sync();
    /*Promise.all([sync(), fetchApi('lists')]).then(vals => {
        console.log(vals);
    });*/
}
