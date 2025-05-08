/**
 * Basic sample plugin inserting abbreviation elements into CKEditor editing area.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

// Register the plugin within the editor.

CKEDITOR.plugins.add( 'lance', {

	// Register the icons.
	icons: 'lance',
	requires: ['fakeobjects'],
	_domLoaded : false,
	_scriptsLoaded : false,
	_editor : null,
	_owner : null,
	_deletedAnnotations : {},
	_syncInterval : null,
	_isSelecting : false, // currently in process of selecting an annotation

	// The plugin initialization logic goes inside this method.
	init: function( editor ) {
		this._editor = editor;
		this._ieFix();
		var config = editor.config.lance || {};
		this._owner = config.annotationsOwner;
		this._setPluginFeatures(editor);
		editor.ui.addToolbarGroup('lance');
//		CKEDITOR.addCss(".cke_annotation{width: 16px !important;height: 21px !important;background-image: url(" + CKEDITOR.getUrl("plugins/lance/css/images/marker3.png") + ");background-size: cover;cursor:pointer;}");
//		CKEDITOR.addCss(".cke_annotation[data-selected='true']{background-image: url(" + CKEDITOR.getUrl("plugins/lance/css/images/marker3-s.png") + ");}");
//		editor.addCss('.cke_script{background-color: #FC0; border-radius:10px; background-image:url(' + CKEDITOR.getUrl('plugins/script/images/placeholder.png') + '); display: block;width:100%;height: 30px;}');
		
		var jQueryPath = config.jQueryPath || "js/jquery.min.js";
		// Define an editor command that opens our dialog.
		editor.addCommand( 'annotate', {
			exec : this.onAnnotate.bind(this),
			async:true,
			editorFocus : false
		} );
		
		// Create a toolbar button that executes the above command.
		editor.ui.addButton( 'annotate', {

			// The text part of the button (if available) and tooptip.
			label: 'Insert Comment',

			// The command to execute on click.
			command: 'annotate',

			// The button placement in the toolbar (toolbar group name).
			toolbar: 'lance',
			
			icon : this.path + "icons/lance.png"
		});

		this._domLoaded = !!this.getDocument();
		
		if (this._domLoaded) {
			this._onDomLoaded();
		}
		
		editor.on("instanceReady", this._onInstanceReady.bind(this));
		editor.on("contentDom", this._onDomLoaded.bind(this));

/*		editor.on("saveSnapshot", function(evt) {
			console.log("save snapshot");
		});
		editor.on("updateSnapshot", function(evt) {
			var e = evt && evt.sender;
			console.log("update snapshot");
		}); */
		
		this._docClickHandler = this._onDocumentClick.bind(this);
		
		if (typeof(jQuery) == "undefined") {
			CKEDITOR.scriptLoader.load(this.path + jQueryPath, this._onScriptsLoaded.bind(this), this)
		}
		else {
			this._scriptsLoaded = true;
		}
		
		
		this._onReady();
	},
	
	afterInit : function(editor) {
		var dataProcessor = editor.dataProcessor;
		var dataFilter = dataProcessor && dataProcessor.dataFilter;
		if (dataFilter) {
			dataFilter.addRules({
				elements: {
					'annotation': this._elementToFake.bind(this)
				}
			}, 5);
		}
	},
	
	_elementToFake : function (editor, element) {
		var fake = editor.createFakeParserElement(element, 'cke_annotation', 'annotation', false);
		this._syncFakeElement(element, fake);
		return fake;
	},
	
	_syncFakeElement : function(element, fake) {
		function getA(e,a) {
			return (e.getAttribute && e.getAttribute(a)) || (e.attributes && e.attributes[a]);
		}
		function setA(e,a,v) {
			return (e.setAttribute && e.setAttribute(a,v)) || (e.attributes && (e.attributes[a]= v));
		}
		setA(fake, "alt", getA(element, "title"));
		setA(fake, "id", getA(element, "id"));
		setA(fake, "data-annotation-id", getA(element, "data-annotation-id"));
		var attr = getA(fake, "data-cke-realelement");
		if (attr) {
			setA(fake, "data-cke-realelement", attr.replace(/%26amp%3B/gi, '%26')); //fix double encoding on ampersands in src
		}
		return fake;
	},
	
	
	_onDomLoaded : function() {
		this._domLoaded = true;
		var doc = this._editor.document;
		
		try {
			this._loadCSS(doc.$);
		}
		catch (e){
			console && console.error && console.error(e);
		}
		doc.removeListener("click", this._docClickHandler);
		doc.on("click", this._docClickHandler);

		this._syncAnnotations();
	},
	
	_syncAnnotations : function() {
		this._deletedAnnotations = {};
		if (! this._owner) {
			return;
		}
		var cnodes = this._editor.document.getElementsByTag("annotation");
		var nodesMap = {};
		var nodes = [];
		if (cnodes) {
			for (var i = 0, count = cnodes.count(); i < count; ++i) {
				var node = cnodes.getItem(i);
				var id = node && node.$ && node.$.getAttribute("data-annotation-id");
				if (id && ! (id in nodesMap)) {
					nodes.push(node);
					nodesMap[id] = node;
				}
				else {
					node.remove();
				}
			}
		}
		
		var annotations = [];
		nodes.each((function(i, node) {
			try {
				var ant = this._extractAnnotation(node);
				if (ant) {
					annotations.push(ant);
				}
			}
			catch (e) {
				console.error(e);
			}
		}).bind(this));
		this._bindToOwner(this._owner, false); // unhook all event handlers while we're adding the data
		this._owner.loadFromData(annotations);

		nodes.each((function(i,node) {
			if (! this._owner.hasAnnotation(node.$.getAttribute("data-annotation-id"))) {
				node.remove();
			}
			else {
				this._setupNode(node);
				//this._registerNode(node);
			}
		}).bind(this));
		this._bindToOwner(this._owner,true);
	},
	
	_extractAnnotation : function(node) {
		if (! node || ! node.$) {
			return null;
		}
		try {
			var str = node.$.getAttribute("data-ant");
			if (str) {
				str = unescape(str);
				var ant = JSON.parse(str);
				return ant;
			}
		}
		catch (e) {
			return null;
		}
	},
	
	_onInstanceReady : function() {
		this._domLoaded = true;
		this._onReady();
	},
	
	_onScriptsLoaded : function() {
		this._scriptsLoaded = true;
		this._onReady();
	},
	
	_loadCSS : function(doc) {
		//console.log("plugin load CSS")
		var head = doc.getElementsByTagName("head")[0];
		var style = doc.createElement("link");
		style.setAttribute("rel", "stylesheet");
		style.setAttribute("type", "text/css");
		style.setAttribute("href", this.path + "css/annotate.css");
		head.appendChild(style);
	},
	

	getDocument : function() {
		return this._editor && this._editor.document && this._editor.document.$;
	},
	
	_onReady : function() {
		if (! this._scriptsLoaded || ! this._domLoaded) {
			return;
		}
		
		var interval = setInterval(function() {
			if (CKEDITOR.filter) {
				clearInterval(interval);
//				CKEDITOR.filter.allow( 'annotation[data-id]', 'Annotation' );
			}
		}, 100);
		this._syncInterval = setInterval(this._syncNodes.bind(this), 100);
		var o;
		if (o = this._owner) {
			o.onClientReady(this);
			this._bindToOwner(o, true);
		}
	},
	
	_bindToOwner : function(owner, bind) {
		if (! owner) {
			return;
		}
		owner.removeListener(null, this);
		if (bind) {
			owner.bind(App.Annotations.Events.ANNOTATION_CREATED, this, this.onAnnotationCreated.bind(this));
			owner.bind(App.Annotations.Events.ANNOTATION_DELETED, this, this.onAnnotationDeleted.bind(this));
			owner.bind(App.Annotations.Events.ANNOTATION_SELECTED, this, this.onAnnotationSelected.bind(this));
			owner.bind(App.Annotations.Events.COMMENT_CREATED, this, this.onCommentCreated.bind(this));
			owner.bind(App.Annotations.Events.COMMENT_DELETED, this, this.onCommentDeleted.bind(this));
			owner.bind(App.Annotations.Events.COMMENT_CHANGED, this, this.onCommentChanged.bind(this));
			owner.bind(App.Annotations.Events.COMMENT_ADDED, this, this.onCommentAdded.bind(this));
			owner.bind(App.Annotations.Events.ENABLED_CHANGED, this, this.onAnnotationsEnabledChanged.bind(this));
		}
	},
	
	_annotationIdToDomId : function(id) {
		if (! id) {
			return "";
		}
		id = id.replace(/[^0-9a-zA-Z-]/g, 'z');
		return "dom-" + id;		
	},
	
	onAnnotationCreated : function(data) {
		var annotation = data&& data.annotation;
		if (! annotation) {
			return;
		}
		var e = this._editor;
		var node = this._createAnnotation(annotation);
		this._populateAnnotation(node, annotation);
		var sel = e.getSelection();
		var ranges = sel.getRanges();
		var lastRange = ranges && ranges.length && ranges[ranges.length - 1];
		if (lastRange && (lastRange.startOffset != lastRange.endOffset)) {
			lastRange.moveToElementEditEnd(lastRange.endContainer);
	//				range.moveToPosition(range.root, lastRange.startOffset);
			sel.selectRanges([lastRange]);
		}
		e.fire("lockSnapshot");
		setTimeout(function() {
			e.fire("unlockSnapsot");
		}, 10);
		var fake = e.createFakeElement( node, 'cke_annotation', 'annotation', false);
		this._syncFakeElement(node, fake);

			//this._elementToFake(e, new CKEDITOR.dom.element(node));
		e.insertElement(fake);
//		this._registerNode(fake);
		
/*		node.$.addEventListener("DOMNodeRemoved", function(e) {
			console.log("annotation " + e.currentTarget.getAttribute("data-annotation-id") + " removed");
//			(e.preventDefault && e.preventDefault()) || (e.returnValue = false);
//			return false;
		}, true); */
	},
	
	onAnnotationDeleted : function(data) {
		var id = data && data.id;
		if (id) {
			var node = this._editor.document.getById(this._annotationIdToDomId(id));
			if (node) {
				node.remove();
			}
		}
	},
	
	onAnnotationChanged : function(data) {
		var annotation = data && data.annotation;
		if (annotation) {
			var node = this._editor.document.getById(this._annotationIdToDomId(annotation.id));
			if (node) {
				this._populateAnnotation(node, annotation);
			}
		}
	},

	onAnnotationSelected: function(data) {
		var annotation = data && data.annotation;
		if (annotation) {
			var node = this._editor.document.getById(this._annotationIdToDomId(annotation.id));
			if (node) {
				this._selectAnnotation(node, annotation);
			}
		}
	},

	
	onCommentCreated: function(data) {
		this.onAnnotationChanged(data);
	},
	onCommentDeleted: function(data) {
		this.onAnnotationChanged(data);
	},
	onCommentChanged: function(data) {
		this.onAnnotationChanged(data);
	},
	onCommentAdded: function(data) {
		this.onAnnotationChanged(data);
	},
	
	onAnnotationsEnabledChanged : function(data) {
		var enabled = data && data.isEnabled;
		var cmd = this._editor.getCommand("annotate");
		if (cmd) {
			cmd.setState(enabled ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED)
		}
	},

	onAnnotate : function(event) {
		if (this._owner) {
			this._owner.insertAnnotation({
				select : true,
				edit : true,
				position : this._getInsertPosition()
			});
		}
	},
	
	_createAnnotation : function(annotation) {
		var doc = this._editor.document;
		var e = doc.createElement( 'annotation' );
//		e.append(doc.createText('\u00A0'));
//		e.setAttribute("href", "#");
		this._setupNode(e);
		
		return e;
	},
	
	_setupNode : function(e) {
		e.setAttribute("tabindex", "0");
		e.setAttribute("data-selected", false);
	},
	
	_selectAnnotation : function(e, annotation) {
		if (e && e.$) {
			if (annotation.isSelected()) {
				e.$.setAttribute("data-selected", "true");
				if (! this._isSelecting) {
					e.scrollIntoView();
				}
			}
			else {
				e.$.removeAttribute("data-selected");
			}
		}
	},

	_populateAnnotation : function(e, annotation) {
		this._selectAnnotation(e, annotation);
		var e$ = e.$;
		if (e$) {
			e$.setAttribute("title", (annotation.text || "").substring(0, 100));
			e$.setAttribute("data-annotation-id", annotation.id);
			e$.setAttribute("id", this._annotationIdToDomId(annotation.id));
			var obj = annotation.saveToObject();
			e$.setAttribute("data-ant", escape(JSON.stringify(obj)));
		}
	},

	
	_onDocumentClick : function(e) {
		if (this._owner) {
			var id = e && e.data && e.data.$ && e.data.$.target && e.data.$.target.getAttribute && e.data.$.target.getAttribute("data-annotation-id");
//			var id = e && e.data && e.data.$ && e.data.$.target && e.data.$.target.getAttribute && e.data.$.target.getAttribute("data-annotation-id");
			if (id) {
				this._isSelecting = true;
				this._owner.selectAnnotation(id, true);
				this._isSelecting = false;
			}
		}
	},
	
/*	_registerNode : function(node) {
		this._allNodes.push(node);
	},
 */		
	_syncNodes : function() {
		if (! this._owner) {
			return;
		}
		var mode = (this._editor && this._editor.mode) || "";
		if (mode != "wysiwyg") {
			return;
		}
		var cnodes = this._editor.document.getElementsByTag("img");

			//this._editor.document.getElementsByTag("annotation");
		var nodes = [];
		var foundMap = {};
		var nodeId;
		for (var i = 0, len = cnodes.count(); i < len; ++i) {
			var nd = cnodes.getItem(i);
			var cls = nd.className || nd.getAttribute("class");
			if (! cls || (cls.indexOf("cke_annotation") < 0)) {
				continue;
			}
			nodeId = nd && nd.getAttribute("data-annotation-id");
			if ((! nodeId) || (nodeId in foundMap)) {
				nd.remove();
			}
			else {
				foundMap[nodeId] = true;
				nodes.push(cnodes.getItem(i));
			}
		}

		var ids = this._owner.getAllAnnotations();
		for (var i = nodes.length - 1; i >= 0; --i) {
			var node = nodes[i];
			var id = node.getAttribute("data-annotation-id");
			var ind;
			if (id && (ind = ids.indexOf(id)) >= 0) {
				ids.splice(ind, 1);
				nodes.splice(i, 1);
			}
		}
		if (ids.length == 0 && nodes.length == 0) { // no changes
			return;
		}
		
		this._bindToOwner(this._owner, false);
		// now ids contains ids not in dom, nodes contains nodes not in owner
		ids.each((function(i, id) {
			var a = this._owner.serializeAnnotation(id);
			if (a) {
				this._deletedAnnotations[id] = a;
			}
			this._owner.deleteAnnotation(id);
		}).bind(this));
		nodes.each((function(i, node) {
			this._setupNode(node);
			var id = node.getAttribute("data-annotation-id");
			var saved = id && this._deletedAnnotations[id];
			
			if (saved) {
				node.setAttribute("data-ant", escape(JSON.stringify(saved)));
				delete this._deletedAnnotations[id];
			}
			var rec = this._extractAnnotation(node);
			if (rec) {
				this._owner.loadAnnotationFromData(rec, this._getInsertPosition(node));
			}
		}).bind(this));
		this._bindToOwner(this._owner, true);
		
	},
	
	_getInsertPosition : function(nodeToInsert) {
		try {
			var address;
			var rawNode;
			if (nodeToInsert) {
				address = nodeToInsert.getAddress();
				rawNode = nodeToInsert.$;
			}
			else {
				rawNode = null;
				var ranges = this._editor.getSelection().getRanges();
				var last = ranges && ranges.length && ranges[ranges.length - 1];
				var position = 999999;
				if (! last || ! last.endContainer) {
					return position;
				}
				address = last.endContainer.getAddress();
				address.push(last.endOffset);
			}
			var ants = this._editor.document.getElementsByTag("annotation");
			for (var i = 0, count = ants.count(); i < count; ++i) {
				var node = ants.getItem(i);
				if (node.$ === rawNode) {
					return i;
				}
				var naddress = node.getAddress();
				var compare = this._compareAddresses(address, naddress);
				if (compare <= 0) {
					return i;
				}
			}
		}
		catch (e) {
		}
		return position;
		
	},
	
	_compareAddresses : function(addr1, addr2) {
		minLevel = Math.min( addr1.length, addr2.length );

		// Determinate preceed/follow relationship.
		for ( var i = 0; i < minLevel; i++ ) {
			var diff = addr1[ i ] - addr2[ i ];
			if (diff < 0) {
				return -1; // addr1 greater
			}
			else if (diff > 0) {
				return 1;
			}
		}
		var diff = addr2.length - addr1.length;
		return diff > 0 ? 1 : diff < 0 ? -1 : 0;
	},
	
	_setPluginFeatures : function(editor) {
		if (editor && editor.filter && editor.filter.addFeature) {
			var attrs = "data-ant,data-annotation-id,id,title,tabindex,data-selected";
			editor.filter.addFeature({
				allowedContent:"img[" + attrs + "]"
			});
			editor.filter.addFeature({
				allowedContent:"annotation[" + attrs + "]"
			});
		}
	},
	
	_ieFix : function() {
		/* Begin fixes for IE */
			Function.prototype.bind = Function.prototype.bind || function () {
				"use strict";
				var fn = this, args = Array.prototype.slice.call(arguments),
				object = args.shift();
				return function () {
					return fn.apply(object,
				args.concat(Array.prototype.slice.call(arguments)));
				};
			};

			/* Mozilla fix for MSIE indexOf */
			Array.prototype.indexOf = Array.prototype.indexOf || function (searchElement /*, fromIndex */) {
				"use strict";
				if (this == null) {
					throw new TypeError();
				}
				var t = Object(this);
				var len = t.length >>> 0;
				if (len === 0) {
					return -1;
				}
				var n = 0;
				if (arguments.length > 1) {
					n = Number(arguments[1]);
					if (n != n) { // shortcut for verifying if it's NaN
						n = 0;
					} else if (n != 0 && n != Infinity && n != -Infinity) {
						n = (n > 0 || -1) * Math.floo1r(Math.abs(n));
					}
				}
				if (n >= len) {
					return -1;
				}
				var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
				for (; k < len; k++) {
					if (k in t && t[k] === searchElement) {
						return k;
					}
				}
				return -1;
			};

			Array.prototype.lastIndexOf = Array.prototype.indexOf || function (searchElement) {
				"use strict";
				if (this == null) {
					throw new TypeError();
				}
				var t = Object(this);
				var len = t.length >>> 0;
				while(--len >= 0) {
					if (len in t && t[len] === searchElement) {
						return len;
					}
				}
				return -1;
			};
	}
});

