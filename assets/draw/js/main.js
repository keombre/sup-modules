// functions:
// draw, then sync
// revoke then sync

function fetchApi(site) {
    return new Promise((resolve, reject) => {
        console.log('fetching ' + 'draw/api/v1/' + site);
        fetch('draw/api/v1/' + site, {
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

function revoke(listID, bookID) {
    Promise.all([
        fetchApi('lists'),
        idbKeyval.get('draws')
    ]).then(data => {
        let list = data[0].lists.find(list => {
            return list.list == listID
        });
        if (typeof list == 'undefined')
            return Promise.reject('not found');
        return Promise.resolve(data);
    }).then(data => {
        let draw = data[1].find(draw => {
            return draw.list == listID
        });
        if (typeof draw == 'undefined')
            return Promise.reject('not drawn');
        if (draw.book != bookID)
            return Promise.reject('book not found');
        
        draw.revoke = 1;
        idbKeyval.set('draws', data[1]);
        return sync();
    });
}

function draw(listID, bookID) {
    Promise.all([
        fetchApi('lists'),
        idbKeyval.get('draws')
    ]).then(data => {
        let list = data[0].lists.find(list => {
            return list.list == listID
        });
        if (typeof list == 'undefined')
            return Promise.reject('not found');
        return Promise.resolve(data);
    }).then(data => {
        let draw = data[1].find(draw => {
            return draw.list == listID
        });
        if (typeof draw != 'undefined')
            return Promise.reject('already drawn');
        return Promise.resolve(data);
    }).then(data => {
        return fetchApi(listID).then(listInfo => {
            let book = Object.values(listInfo.books).find(book => {
                return book.id == bookID
            });
            if (typeof book == 'undefined')
                return Promise.reject('book not found');
            return Promise.resolve(data);
        });
    }).then(data => {
        data[1].push({'list': listID, 'book': bookID});
        idbKeyval.set('draws', data[1]);
        return sync();
    });
}
