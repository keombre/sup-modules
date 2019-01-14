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
    if (json.status == 'success') {
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

function draw(entry) {
    return new Promise((resolve, reject) => {
        console.log('draw: uploading');
        fetchApi(entry.list + '/draw/' + entry.book).then(resolve).catch(e => {
            if (e.code != 3) {
                reject(new Error(e.message));
            } else {
                console.log('draw: forcing draw')
                fetchApi(entry.list + '/revoke/' + e.book).then(() => {
                    fetchApi(entry.list + '/draw/' + entry.book)
                        .then(resolve, reject);
                }, reject);
            }
        })
    });
}

function seed(e) {
    if (typeof e == 'undefined') {
        e = []
        idbKeyval.set('draws', e).then(() => {
            return Promise.resolve(e);
        })
    } else {
        return Promise.resolve(e);
    }
}

function sync() {
    console.log('sync: syncing..');
    Promise.all([idbKeyval.get('draws').then(seed), fetchApi('draws?all').then(e => e.draws)])
        .then(data => {
            let local = data[0].filter(x => !data[1].includes(x));
            
            console.log('sync: uploading ' + local.length + ' entries');
            local.forEach(draw);

            let concat = data[1].concat(local);
            console.log('sync: saving ' + concat.length + ' entries')
            idbKeyval.set('draws', concat);
        });
}

export function init() {
    draw({'time': 0, 'list': 293638, 'book': 1})
    /*Promise.all([sync(), fetchApi('lists')]).then(vals => {
        console.log(vals);
    });*/
}
