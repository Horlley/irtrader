window.Core = {

get(url)
{
    return fetch(url,{
        headers:{
            'X-Requested-With':'XMLHttpRequest'
        }
    }).then(r=>r.json());
},

post(url,data={})
{
    return fetch(url,{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':document
                .querySelector('meta[name="csrf-token"]')
                .content
        },
        body:JSON.stringify(data)
    }).then(r=>r.json());
}

};