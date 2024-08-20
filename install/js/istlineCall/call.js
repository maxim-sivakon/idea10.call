istlineCall = function (obj) {
    var eventID = false;
    var ownerID = false;
    var entity = false;

    t = $(obj)
    if (t.attr('href').replace("callto:", "").length == 3) {
        var tel = t.attr('href').replace("callto:", "");
        $.ajax({
            url: "/bitrix/tools/istlineCall/istlineCall.php?targetN=" + tel,
            method: "GET",
            success: function (d) {
                if (d.length > 0) {
                    alert(d)
                }
            }
        })
        return true;
    }
    while (t.length > 0) {
        var m = String(t.attr("id"))

        if (t.closest(".popup-window-content[id^='popup-window-content-CrmActivityCallView']").length > 0) {
            m = t.closest(".popup-window-content[id^='popup-window-content-CrmActivityCallView']").attr("id").replace("popup-window-content-CrmActivityCallView", "");
            eventID = m;


        } else if (t.closest(".feed-post-cont-wrap[id^='sonet_log_day_item']").length > 0) {
            m = t.closest(".feed-post-cont-wrap[id^='sonet_log_day_item']").attr('class').split(" ");
            $.each(m, function (index, item) {
                m = String(item);
                if (m.indexOf("sonet-log-item-where-CRMACTIVITY") + 1) {
                    m = m.replace("sonet-log-item-where-CRMACTIVITY-", "")
                    m = m.replace("-all", "")
                    eventID = m;
                    return false;
                }
            });
        } else if (BX.CrmQuickPanelView && BX.CrmQuickPanelView.getDefault()._entityId > 0) {
            ownerID = BX.CrmQuickPanelView.getDefault()._entityId
            entity = BX.CrmQuickPanelView.getDefault()._entityTypeName
        } else if ($(t).attr('id') == "CRM_LEAD_LIST_V12") {
            ownerID = $(obj).closest("tr").find("input[name^='ID']").val();
            entity = "Lead"
        } else if ($(t).attr('id') == "CRM_DEAL_LIST_V12") {
            ownerID = $(obj).closest("tr").find("input[name^='ID']").val();
            entity = "Deal"
        } else if ($(t).attr('id') == "CRM_CONTACT_LIST_V12") {
            ownerID = $(obj).closest("tr").find("input[name^='ID']").val();
            entity = "Contact"
        } else if ($(t).attr('id') == "CRM_COMPANY_LIST_V12") {
            ownerID = $(obj).closest("tr").find("input[name^='ID']").val();
            entity = "Company"
        }
        if (eventID || ownerID) {
            break;
        }
        t = t.parent()
    }
    if (eventID) {
        $.ajax({
            url: "/bitrix/tools/istlineCall/istlineCall.php?targetN=" + $(obj).attr("href").replace("callto:", "") + "&eventID=" + eventID + '&action=call-from-portal',
            method: "GET",
            success: function (d) {
                if (d.length > 0) {
                    alert(d)
                }
            }
        })
    } else if (ownerID && entity) {
        $.ajax({
            url: "/bitrix/tools/istlineCall/istlineCall.php?targetN=" + $(obj).attr("href").replace("callto:", "") + "&ownerID=" + ownerID + "&ownerType=" + entity + '&action=call-from-portal',
            method: "GET",
            success: function (d) {
                if (d.length > 0) {
                    alert(d)
                }
            }
        })
    }
}

istlineCall.WindowClose = false;

istlineCall.action = function (params) {
    var d = {};
    $.each(params, function (k, v) {
        d[k] = v
    })
    $.ajax({
        url: "/bitrix/tools/istlineCall/istlineCall.php",
        data: d,
        cache: false,
        method: "GET",
        success: function (d) {
            if (!istlineCall.WindowClose) {
                $("body").append(d)
            }
            istlineCall.WindowClose = false;
        }
    })
}


istlineCall.closeCallCard = function (params) {
    if ($("#act" + params.actID).length > 0) {
        $("#act" + params.actID).parent().remove();
    } else {
        istlineCall.WindowClose = true;
    }
}

istlineCall.userEvent = false;

$(document).ready(function () {
    $("a[href^='callto']").each(function () {
        if ($(this).attr('href').replace("callto:", "").length == 3) {
            $(this).attr("onclick", "istlineCall(this);return false;")
        }
    })
})

BX.ready(function () {
    BX.addCustomEvent("onPullEvent-istline.call", BX.delegate(function (command, params) {
        if (!BX.desktop) {
            if (command == 'start_call') {
                data = {
                    action: command,
                    phonenumber: params["phone"],
                    actID: params["actID"]
                }
                istlineCall.action(data);
            }
            if (command == 'close_call') {
                data = {
                    action: command,
                    phonenumber: params["phone"],
                    actID: params["actID"]
                }
                istlineCall.closeCallCard(data);
            }
        }
    }, this));
})

;
(function () {
    if (!!BX.CFileInput) return;

    BX.CFileInput = function (ID, INPUT_NAME, CID, upload_path, bMultiple) {
        this.ID = ID;
        this.INPUT_NAME = INPUT_NAME;
        this.CID = CID;
        this.upload_path = upload_path;

        this.multiple = !!bMultiple;

        this.INPUT = null;
        this.LIST = null;

        this.bInited = false;

        this.FILES = [];

        BX.CFileInput.Items[ID] = this;

        BX.ready(BX.delegate(this.Init, this));
    }

    BX.CFileInput.Items = {};

    BX.CFileInput.prototype.setFiles = function (arFiles) {
        if (!BX.type.isArray(arFiles)) {
            return;
        }

        this.Clear();
        this.FILES = arFiles;

        if (this.bInited) {
            setTimeout(BX.delegate(function () {
                this.Callback(this.FILES, 'init');
            }, this), 1);
        }
    }

    BX.CFileInput.prototype.Init = function () {
        if (this.bInited)
            return;

        this.INPUT = BX("file_input_" + this.ID);
        this.LIST = BX("file_input_upload_list_" + this.ID);

        BX.bind(this.INPUT, "change", BX.proxy(this.OnChange, this));

        setTimeout(BX.delegate(function () {
            this.bInited = true;
            this.Callback(this.FILES, 'init');
        }, this), 1);
    }

    BX.CFileInput.prototype.CreateFileEntry = function (file, uniqueID) {
        return BX.create("LI", {
            props: {className: "uploading", id: "file-" + file.fileName + "-" + uniqueID},
            children: [
                BX.create("A", {
                    props: {href: "", target: "_blank", className: "upload-file-name"},
                    text: file.fileName,
                    events: {click: BX.PreventDefault}
                }),
                BX.create('SPAN', {
                    props: {className: 'upload-file-size'},
                    children: [typeof file.fileSize !== 'undefined' ? ('&nbsp;' + file.fileSize) : null]
                }),
                BX.create("I"),
                BX.create("A", {
                    props: {href: "javascript:void(0)", className: "delete-file"},
                    events: {click: BX.proxy(this._deleteFile, this)}
                })
            ]
        });
    }

    BX.CFileInput.prototype.OnChange = function () {
        var files = [];

        if (this.INPUT.files && this.INPUT.files.length > 0) {
            files = this.INPUT.files;
        }
        else {
            var filePath = this.INPUT.value;
            var fileTitle = filePath.replace(/.*\\(.*)/, "$1").replace(/.*\/(.*)/, "$1");
            files = [{fileName: fileTitle}];
        }

        var uniqueID;
        do {
            uniqueID = Math.floor(Math.random() * 99999);
        } while (BX("iframe-" + uniqueID));

        if (!this.multiple) {
            BX.cleanNode(this.LIST);
        }

        for (var i = 0; i < files.length; i++) {
            if (!files[i].fileName && files[i].name) {
                files[i].fileName = files[i].name;
            }
            this.LIST.appendChild(this.CreateFileEntry(files[i], uniqueID));
        }

        this.Send(uniqueID);
    }

    BX.CFileInput.prototype.Send = function (uniqueID) {
        var iframeName = "iframe-" + uniqueID;
        var iframe = BX.create("IFRAME", {
            props: {name: iframeName, id: iframeName},
            style: {display: "none"}
        });
        document.body.appendChild(iframe);

        var originalParent = this.INPUT.parentNode, originalName = this.INPUT.name;
        originalParent.removeChild(this.INPUT);

        this.INPUT.name = 'mfi_files[]';

        // hack: the only way to surely set enctype=multipart/form-data for this form
        var f = BX.create('DIV');
        f.innerHTML = '<form enctype="multipart/form-data"></form>';
        var form = f.firstChild;

        BX.adjust(form, {
            props: {
                method: "POST",
                action: this.upload_path,
                target: iframeName
            },
            style: {display: "none"},
            children: [
                this.INPUT,
                BX.create("INPUT", {
                    props: {
                        type: "hidden",
                        name: "sessid",
                        value: BX.bitrix_sessid()
                    }
                }),
                BX.create("INPUT", {
                    props: {
                        type: "hidden",
                        name: "uniqueID",
                        value: uniqueID
                    }
                }),
                BX.create("INPUT", {
                    props: {
                        type: "hidden",
                        name: "cid",
                        value: this.CID
                    }
                }),
                BX.create("INPUT", {
                    props: {
                        type: "hidden",
                        name: "controlID",
                        value: (!!this.ID ? this.ID : '')
                    }
                }),
                BX.create("INPUT", {
                    props: {
                        type: "hidden",
                        name: "mfi_mode",
                        value: "upload"
                    }
                })
            ]
        });
        BX.onCustomEvent(this, 'onSubmit', []);
        window['FILE_UPLOADER_CALLBACK_' + uniqueID] = BX.proxy(this.Callback, this);
        document.body.appendChild(f);
        BX.submit(form, 'mfi_save', 'Y', BX.delegate(function () {
            this.INPUT.name = originalName;
            BX.unbind(this.INPUT, "change", BX.proxy(this.OnChange, this));
            this.INPUT = originalParent.appendChild(BX.create('INPUT', {
                attrs: {
                    name: originalName,
                    id: this.INPUT.id,
                    type: 'file',
                    size: '1',
                    multiple: 'multiple'
                }
            }));
            BX.bind(this.INPUT, "change", BX.proxy(this.OnChange, this));

            BX.cleanNode(f, true);
            if (!this.multiple) {
                this.INPUT.disabled = true;
            }
        }, this));
    }

    BX.CFileInput.prototype.Clear = function () {
        if (this.LIST) {
            while (this.LIST.childNodes.length > 0) {
                this.LIST.removeChild(this.LIST.childNodes[0]);
            }
        }

        this.FILES = [];
    }

    BX.CFileInput.prototype.Callback = function (files, uniqueID) {
        if (!this.bInited) {
            this.Init();
            return;
        }

        BX.show(this.LIST);
        var err = false, node;
        for (var i = 0; i < files.length; i++) {
            var elem = BX("file-" + files[i].fileName + "-" + uniqueID);
            if (!elem) {
                elem = this.LIST.appendChild(this.CreateFileEntry(files[i], uniqueID + Math.random()));
            }

            if (files[i].fileID) {
                BX.removeClass(elem, "uploading");
                BX.addClass(elem, "saved");
                BX.adjust(elem.firstChild, {props: {href: files[i].fileURL}});
                BX.adjust(elem.firstChild.nextSibling, {html: '&nbsp;' + files[i].fileSize});
                BX.unbindAll(elem.firstChild);
                BX.unbindAll(elem.lastChild);
                BX.bind(elem.lastChild, "click", BX.proxy(this._deleteFile, this));
                node = BX.create("INPUT", {
                    props: {
                        type: "hidden",
                        name: this.INPUT_NAME + (this.multiple ? '[]' : ''),
                        value: files[i].fileID
                    }
                });
                elem.appendChild(node);
                BX.onCustomEvent(this, 'onAddFile', [files[i].fileID, this, files[i], node]);
            }
            else {
                BX.onCustomEvent(this, 'onErrorFile', [files[i], this]);
                err = true;
                BX.cleanNode(elem, true);
            }
        }
        BX.onCustomEvent(this, 'onDone', [files, uniqueID, err, this]);
        if (this.LIST.childNodes.length <= 0)
            this.INPUT.disabled = false;
        window['FILE_UPLOADER_CALLBACK_' + uniqueID] = null;
        BX.cleanNode(BX("iframe-" + uniqueID), true);

        BX.onCustomEvent(this, 'onFileUploaderChange', [files, this]);
    }

    BX.CFileInput.prototype._deleteFile = function (e) {
        if (!e)
            return false;
        var node = (BX(e) ? BX(e).previousSibling : BX.proxy_context), id;
        var bSaved = BX.hasClass(node.parentNode, "saved");
        if (!bSaved || confirm(BX.message("MFI_CONFIRM"))) {
            id = node.nextSibling.value;
            if (bSaved) {
                var data = {
                    fileID: node.nextSibling.value,
                    sessid: BX.bitrix_sessid(),
                    cid: this.CID,
                    controlID: (!!this.ID ? this.ID : ''),
                    mfi_mode: "delete"
                };
                BX.ajax.post(this.upload_path, data);
            }
            BX.remove(node.parentNode);
            BX.onCustomEvent(this, 'onDeleteFile', [id, this]);
            BX.onCustomEvent(this, 'onFileUploaderChange', [[id], this]);
        }

        if (this.LIST.childNodes.length <= 0)
            this.INPUT.disabled = false;

        BX.PreventDefault(e);
    }


})();
