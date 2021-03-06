/* Scat functionality */

"use strict";

class ScatUtils {

  htmlToElement (html) {
    let template= document.createElement('template')
    template.innerHTML= html.trim()
    return template.content.firstChild
  }

  // Pop up a dialog
  dialog (from, name, data= {}) {
    let url= name

    if (Object.keys(data)) {
      let query= Object.keys(data)
                   .map(k => encodeURIComponent(k) + '=' +
                             encodeURIComponent(data[k]))
                   .join('&')
      url+= '?' + query
    }

    if (from.disabled) return false
    from.disabled= true

    return fetch(url)
      .then((response) => {
        if (response.status >= 200 && response.status < 300) {
          return Promise.resolve(response)
        }
        return Promise.reject(new Error(response.statusText))
      })
      .then((res) => { return res.text() })
      .then((text) => {
        console.log("Loaded '" + url + "'.")
        let modal= this.htmlToElement(text)
        document.body.insertAdjacentElement('beforeend', modal)
        $(modal).on('show.bs.modal', function(e) {
          // Re-inject the script to get it to execute
          let code= this.getElementsByTagName('script')[0].innerHTML
          let script= document.createElement('script')
          script.modal= this
          script.appendChild(document.createTextNode(code))
          this.appendChild(script).parentNode.removeChild(script)
          /* Attach dialog to each object with event handler */
          this.querySelectorAll('*').forEach((el) => {
            if (typeof el.onclick === 'function' ||
                typeof el.onsubmit === 'function') {
              el.dialog= this
            }
          })
        })
        $(modal).on('hidden.bs.modal', function(e) {
          $(this.script).remove()
          $(this).remove()
        });
        $(modal).modal()
        from.disabled= false
        return Promise.resolve($(modal))
      })
  }

  generateSlug (...parts) {
    return import('/js/url_slug.js')
      .then(m => {
          return m.url_slug(parts.join('-'),
                            {
                              replacements : {
                                '&': 'and',
                                '#': 'hashbrown-'
                              }
                            })
      })
  }

  printDocument (name, options) {
    let el= document.getElementById('scat-print')
    if (el) {
      el.remove()
    }

    let url= '/print/' + name + '.php?' + $.param(options)
    let text= '<iframe id="scat-print" src="' + url + '"></iframe>'

    let lpr= this.htmlToElement(text)
    lpr.setAttribute('display', 'none');
    lpr.addEventListener('load', (ev) => {
      /* If we got JSON, we printed directly */
      if (ev.target.contentDocument.contentType != 'application/json') {
        ev.target.contentWindow.print()
      }
    })

    document.body.insertAdjacentElement('beforeend', lpr)
  }

  // format number as $3.00 or ($3.00)
  amount (val) {
    if (typeof(val) == 'function') {
      val= val()
    }
    if (typeof(val) == 'undefined' || val == null) {
      return ''
    }
    if (typeof(val) == 'string') {
      val= parseFloat(val)
    }
    if (val < 0.0) {
      return '($' + Math.abs(val).toFixed(2) + ')'
    } else {
      return '$' + val.toFixed(2)
    }
  }

  api (func, args, opts) {
    let url= '/api/' + func + '.php';
    const formData= new FormData()
    for (let prop in args) {
      formData.append(prop, args[prop])
    }

    return fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: formData
    })
    .then((response) => {
      if (response.status >= 200 && response.status < 300) {
        return Promise.resolve(response)
      }
      return Promise.reject(new Error(response.statusText))
    })
    // XXX catch data.error and alert()?
  }
}

let scat= new ScatUtils()
