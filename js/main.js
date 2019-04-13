(async function() {
    const body = document.querySelector('body');
    const frontpage = body.querySelector('#frontpage');
    const searchpage = body.querySelector('#searchpage');
    const postpage = body.querySelector('#postpage');

    const createPost = function(post, target) {
        let li = document.createElement('li');
        let a = document.createElement('a');
        a.innerHTML = post["data"]["title"];
        a.href = "#!id/"+post['postID'];
        li.appendChild(a);
        target.appendChild(li);   
    };

    const render = async () => {
        let rID = /!id\/(\d+)/.exec(location.hash);
        let sValue = /!search\/(.*)/.exec(location.hash);
        if (rID !== null) {
            frontpage.classList.add('hidden');
            searchpage.classList.add('hidden');
            postpage.classList.remove('hidden');
            frontpage.style.display = '';
            searchpage.style.display = '';
            postpage.style.display = '';

            // blog-api usage below
            try {
                let post = await (await fetch("/api/?get&postID="+rID[1])).json();
                postpage.querySelector('#title').innerHTML = post["data"]["title"];
                postpage.querySelector('#header').innerHTML = post["data"]["header"];
                postpage.querySelector('#content').innerHTML = post["data"]["contentParsed"];
            } catch (e) {
                location.hash = '';
            }
            // blog-api usage above
        } else if (sValue !== null) {
            frontpage.classList.add('hidden');
            searchpage.classList.remove('hidden');
            postpage.classList.add('hidden');
            frontpage.style.display = '';
            searchpage.style.display = '';
            postpage.style.display = '';

            // blog-api usage below
                // unavaible right now :(
            // blog-api usage above
        } else {
            frontpage.classList.remove('hidden');
            searchpage.classList.add('hidden');
            postpage.classList.add('hidden');
            frontpage.style.display = '';
            searchpage.style.display = '';
            postpage.style.display = '';

            const latestHTML = frontpage.querySelector('#latest ul');
            latestHTML.innerHTML = '';
            const featuredHTML = frontpage.querySelector('#featured ul');
            featuredHTML.innerHTML = '';
    
            // blog-api usage below
            let posts = await (await fetch("/api/?get")).json();
            let featured = [];
    
            let i = 0;
            for(let post of posts) {
                if (post === null) continue;
                if (/featured/.exec(post['data']['tags']) !== null) featured.push(post);
                if (i < 3) {
                    createPost(post, latestHTML);
                    i++;
                }
            }
    
            for(let i = 0; i < featured.length; i++) {
                if (i == 6) break;
                createPost(featured[i], featuredHTML);
            }
            // blog-api usage above
        }
    }
    render();

    const searchInput = searchpage.querySelector('#searchpageSearchInput');
    const searchTrigger = frontpage.querySelector('#frontpageSearchInput');
    searchTrigger.addEventListener('focus', async ()=>{
        location.hash = '#!search/'+searchInput.value;
        await render();
        searchInput.focus();
    });
    searchInput.addEventListener('keyup', e=>{
        location.hash = '#!search/'+searchInput.value;
    });
    searchInput.addEventListener('blur', e=>{
        searchTrigger.value = searchInput.value;
        location.hash = '';
    });

    let lastHash = location.hash;
    setInterval(async ()=>{
        if (lastHash != location.hash) {
            lastHash = location.hash;
            render();
        }
    }, 100);
})();