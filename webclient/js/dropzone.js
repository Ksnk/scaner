$(function () {

    if(typeof(DropPlus)=='undefined')DropPlus={};
    DropPlus = $.extend({
        maxpicture: 50000,
        maxfile: 50000,
        hugefile: 2000000,
        maxwidth: 1024,
        maxheight: 1024
    }, DropPlus||{});

    var cs = {
        /**
         *  Want to learn more? follow top of this file.
         */
        maxpicture: DropPlus.maxpicture,
        maxfile: DropPlus.maxfile,
        hugefile:DropPlus.hugefile,
        maxwidth: DropPlus.maxwidth,
        maxheight: DropPlus.maxheight,
        debug: DropPlus.debug||false,
        oncomplete: DropPlus.oncomplete||function(){
            var x=this.upload.attr('data-oncomplete');
            if(this.result && x)return eval(x);
        },
        result:true,

        formData: false,
        upload: null, // $-ed input-type control
        tmpdata: {},
        stack: [],
        /**
         *  pop, push, stack - some sort of "deferred" jQuery object but without jQuery.
         */
        executing: false,
        ajax_handle: function(data) {
            console.log('handle :',data);
//        $('#uploads ul').append('<li>' + data.name + '</li>');
        },
        push: function (par) {
            this.init();
            for (var i = par.length - 1, f; f = par[i]; i--)
                this.stack.push(f);
        },
        pop: function () {
            if (this.stack.length == 0) {
                this.executing = false;
            }
            if (this.executing)
                return;
            this.executing = true;
            var curr = this.stack.pop();
            if (this.debug && curr)console.log(curr[0]);
            while (curr && false !== this[curr[0]].call(this, curr[1] || this.tmpdata, curr[2] || false, curr[3] || false, curr[4] || false)) {
                if (this.stack.length > 0) {
                    curr = this.stack.pop();
                    if (this.debug && curr)console.log(curr[0]);
                } else {
                    break;
                }
            }
            this.executing = false;
        },

        error: function (mess) {
            $('.error').show().append('<p>' + mess + '</p>');
        },

        /**
         * making a blob from dataUri
         */
        makeblob: function (dataURI) {
            // convert base64/URLEncoded data component to raw binary data held in a string
            var byteString;
            if (dataURI.split(',')[0].indexOf('base64') >= 0)
                byteString = atob(dataURI.split(',')[1]);
            else
                byteString = unescape(dataURI.split(',')[1]);

            // separate out the mime component
            var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

            // write the bytes of the string to a typed array
            var ia = new Uint8Array(byteString.length);
            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }

            this.tmpdata = new Blob([ia], {type: mimeString});
            console.log(this.tmpdata.size);
        },

        /**
         * building the dataUri from file
         */
        reader: function (f) {
            var that = this, reader = new FileReader();
            this.size = f.size;
            this.name = f.name;

            reader.onload = function (e) {
                that.tmpdata = e.target.result;
                that.pop();
            };
            reader.readAsDataURL(f);
            return false
        },

        /**
         * wait till img.
         */
        imgsrc: function (src) {
            var that = this, i = new Image();
            i.onload = function () {
                that.pop();
            };
            i.src = src;
            that.tmpdata = i;
            return false;
        },

        add: function (f, name, fileField) {
            this.formData.append(fileField, f, name || this.name);
        },

        chunk: function (f, name, fileField, start) {
            var chunk;
            if ('mozSlice' in f) {
                chunk = f.mozSlice(start, this.maxfile);
            } else if ('webkitSlice' in f) {
                chunk = f.webkitSlice(start, this.maxfile);
            } else {
                chunk = f.slice(start, Math.min(f.size,start+this.maxfile));
            }
            if (this.debug)
                console.log("chunksize = " + chunk.size + " "+f.size+" "+start);
            this.formData.append('chunked', start||0);
            this.formData.append(fileField, chunk, name || this.name);
        },
        // @from: http://stackoverflow.com/questions/18922880/html5-canvas-resize-downscale-image-high-quality/19223362#19223362
        resample_hermite: function (canvas, img_obj, W2, H2) {
            var time1 = Date.now(),
                W = img_obj.naturalWidth,
                H = img_obj.naturalHeight;
            W2 = Math.round(W2);
            H2 = Math.round(H2);
            canvas.width = W;
            canvas.height = H;
            canvas.getContext('2d').drawImage(img_obj, 0, 0);
            var img = canvas.getContext("2d").getImageData(0, 0, W, H),
                img2 = canvas.getContext("2d").getImageData(0, 0, W2, H2),
                data = img.data,
                data2 = img2.data,
                ratio_w = W / W2,
                ratio_h = H / H2,
                ratio_w_half = Math.ceil(ratio_w / 2),
                ratio_h_half = Math.ceil(ratio_h / 2);

            for (var j = 0; j < H2; j++) {
                for (var i = 0; i < W2; i++) {
                    var x2 = (i + j * W2) * 4;
                    var weight = 0;
                    var weights = 0;
                    var weights_alpha = 0;
                    var gx_r = 0, gx_g = 0, gx_b = 0, gx_a = 0;
                    var center_y = (j + 0.5) * ratio_h;
                    for (var yy = Math.floor(j * ratio_h); yy < (j + 1) * ratio_h; yy++) {
                        var dy = Math.abs(center_y - (yy + 0.5)) / ratio_h_half;
                        var center_x = (i + 0.5) * ratio_w;
                        var w0 = dy * dy;//pre-calc part of w
                        for (var xx = Math.floor(i * ratio_w); xx < (i + 1) * ratio_w; xx++) {
                            var dx = Math.abs(center_x - (xx + 0.5)) / ratio_w_half;
                            var w = Math.sqrt(w0 + dx * dx);
                            if (w >= -1 && w <= 1) {
                                //hermite filter
                                weight = 2 * w * w * w - 3 * w * w + 1;
                                if (weight > 0) {
                                    dx = 4 * (xx + yy * W);
                                    //alpha
                                    gx_a += weight * data[dx + 3];
                                    weights_alpha += weight;
                                    //colors
                                    if (data[dx + 3] < 255)
                                        weight = weight * data[dx + 3] / 250;
                                    gx_r += weight * data[dx];
                                    gx_g += weight * data[dx + 1];
                                    gx_b += weight * data[dx + 2];
                                    weights += weight;
                                }
                            }
                        }
                    }
                    data2[x2] = gx_r / weights;
                    data2[x2 + 1] = gx_g / weights;
                    data2[x2 + 2] = gx_b / weights;
                    data2[x2 + 3] = gx_a / weights_alpha;
                }
            }
            if (this.debug)
                console.log("hermite = " + (Math.round(Date.now() - time1) / 1000) + " s");
            canvas.getContext("2d").clearRect(0, 0, Math.max(W, W2), Math.max(H, H2));
            canvas.width = W2;
            canvas.height = H2;
            canvas.getContext("2d").putImageData(img2, 0, 0);
        },
        // data - string - data:src
        compress: function (img_obj) {
            var mime_type = "image/jpeg";

            var cvs = document.createElement('canvas');

            var k = 1;
            if (img_obj.naturalWidth > this.maxwidth || img_obj.naturalHeight > this.maxheight) {
                k = Math.min(this.maxwidth / img_obj.naturalWidth, this.maxheight / img_obj.naturalHeight);
            }
            // now draw scaled(?) image
            cvs.width = Math.round(k * img_obj.naturalWidth);
            cvs.height = Math.round(k * img_obj.naturalHeight);
            //
            if (this.usehermit)
                this.resample_hermite(cvs, img_obj, cvs.width, cvs.height);
            else
                cvs.getContext("2d").drawImage(img_obj, 0, 0, cvs.width, cvs.height);
            var quality = 95;
            do {
                this.makeblob(cvs.toDataURL(mime_type, quality / 100));
            } while ((quality -= 5) > 20 && ( this.maxpicture < this.tmpdata.size));
        },

        /**
         * sending formData to the server
         */
        send: function () {
            this.wrapper(true);
            var xhr = new XMLHttpRequest(), that = this;
            xhr.onload = function () {
                that.newFormData();
                var data = {};
                try {
                    var $txt = (this.responseText || '""').toString();
                    $txt = $txt.replace(/^[^{]*|[^}]*$/g, '');
                    if ('' != $txt) {
                        data = JSON.parse($txt);
                        //data = (new Function('return ' + $txt))();
                    } else if (this.debug) {
                        console && console.log && console.log(strdata || '""');
                    }
                } catch (e) {
                    data = {};
                }
                that.ajax_handle(data);
                that.pop();
            };
            xhr.onprogress = function (evt) {
                if (this.debug)
                    console.log(evt);
                if (evt.lengthComputable) {
                    if (this.debug)
                        console.log('progress:', (evt.loaded / evt.total) * 100);
                    return (evt.loaded / evt.total) * 100;
                }
                return null;
            };
            xhr.open('post', window.location.href);
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            var form = $(this.upload[0].form),
                a,data = this.upload.data('data') || {};
/*            x = x.split('&');
            for (a in x)if (x.hasOwnProperty(a)) {
                var u = x[a].split('=');
                data[u[0]] = u[1];
            }*/
            for (a in data)if ('' != a && data.hasOwnProperty(a)) {
                this.formData.append(a, data[a]);
            }
            xhr.send(that.formData);
            return false;
        },
        wrapper: function (on) {
            if (on === true) {
                console.log('wrapper show');
                $('.wrapper_timeout').show(20);
            } else {
                console.log('wrapper off');
                if (cs.timeout_circle) clearTimeout(cs.timeout_circle);
                $('.wrapper_timeout').hide(20);
            }
            //wrapper(on);
        },

        /**
         * инициализация всех внутренних переменных
         */
        init: function () {
            if (!this.formData) {
                this.newFormData();
                this.stack = [];
            }
        },
        newFormData: function () {
            this.formData = new FormData();
        },
        handle: function (upload, files) {
            this.wrapper(true);
            if (!upload) return;
            if (upload.attr('name')) {
                upload.attr('data-name', upload.attr('name'));
                upload.removeAttr('name');
            }
            this.upload = upload;
            this.push([
                ['wrapper', 0]
            ]);
            for (var i = 0, f; f = files[i]; i++) {
                if (this.debug) console.log(f.name, f.type, f.size);
                if (f.size <= 0) {// directories
                    this.error('Impossible to upload directory `' + f.name + '`');
                    continue;
                }
                if (f.name.match(/\.(php\d?|exe)$/)) {// fresh viruses
                    this.error('sorry, can\'t load executables `' + f.name + '`');
                    continue;
                }
                if (f.type.match('image.*') && f.size > this.maxpicture) {
                    this.push([
                        ['reader', files[i]],
                        ['imgsrc'],
                        ['compress'],
                        ['add', null, files[i].name, upload.attr('data-name')],
                        ['send']
                    ]);
                } else if (f.size <= this.maxfile) {
                    this.push([
                        ['add', files[i], files[i].name, upload.attr('data-name')],
                        ['send']
                    ]);
                } else if (f.size <= this.hugefile) {
                    var x= Math.floor(f.size/this.maxfile);
                    while(x>=0){
                        this.push([
                            ['chunk', files[i], files[i].name,upload.attr('data-name'),x*this.maxfile],
                            ['send']
                        ]);
                        x--;
                    }
                } else {
                    this.error('sorry, file too big `' + f.name + '`(' + f.size + ')');
                }
            }
            this.pop();
        }
    };

    $(document).on('drop', '.dropzone',function (e) {
        $('.dropzone').removeClass('dropme');
        cs.handle($('input[type=file]', this), e.originalEvent.dataTransfer.files);
        e.preventDefault();
    }).on('dragover',function () {
        $('.dropzone').addClass('dropme');
        return false;
    }).on('dragleave',function () {
        $('.dropzone').removeClass('dropme');
        return false;
    }).on('change', '.file_upload input[type=file]', function (e) {
        cs.handle($(this), e.target.files);
    });
});
