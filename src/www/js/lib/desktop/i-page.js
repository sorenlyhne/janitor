u.bug_console_only = true;

Util.Objects["page"] = new function() {
	this.init = function(page) {
		u.bug("init page:" + page)

		window.page = page;

		// show parentnode comment in console
		u.bug_force = true;
		u.bug("This is built using Manipulator, Janitor and Detector");
		u.bug("Visit http://parentnode.dk for more information");
		u.bug("Free lunch for new contributers ;-)");
		u.bug_force = false;


		var i, node;


		// main elements
		// header element
		page.hN = u.qs("#header", page);
		// content element
		page.cN = u.qs("#content", page);

		// navigation element
		page.nN = u.qs("#navigation", page);
		if(page.nN) {
			page.nN = page.hN.appendChild(page.nN);
		}
		// footer element
		page.fN = u.qs("#footer", page);


		// global scroll handler 
		page.resized = function() {

			page.browser_h = u.browserH();
			page.browser_w = u.browserW();

			// forward resize event to current scene
			if(page.cN && page.cN.scene && typeof(page.cN.scene.resized) == "function") {
				page.cN.scene.resized();
			}

		}

		// global scroll handler 
		page.scrolled = function() {
//			u.bug("page scrolled")

			page.scroll_y = u.scrollY();

			// forward scroll event to current scene
			if(page.cN && page.cN.scene && typeof(page.cN.scene.scrolled) == "function") {
				page.cN.scene.scrolled();
			}

		}

		page.orientationchanged = function() {

			// forward scroll event to current scene
			if(page.cN && page.cN.scene && typeof(page.cN.scene.orientationchanged) == "function") {
				page.cN.scene.orientationchanged();
			}
		}

		// Page is ready - called from several places, evaluates when page is ready to be shown
		page.ready = function() {
//				u.bug("page ready")

			// page is ready to be shown - only initalize if not already shown
			if(!this.is_ready) {

				// page is ready
				this.is_ready = true;

				// set resize handler
				u.e.addEvent(window, "resize", page.resized);
				// set scroll handler
				u.e.addEvent(window, "scroll", page.scrolled);
				// set orientation change handler
				u.e.addEvent(window, "orientationchange", page.orientationchanged);

				// initialize header
				page.initHeader();

				// adds notifier and page.notify function
				u.notifier(page);

				// adds notifier and page.notify function
				u.navigation();

				// initial resize
				page.resized();
			}
		}

		// TODO: dummy navigation handler - just refreshes the page
		page.cN.navigate = function(url) {
			
			u.bug("page.navigated");
			location.href = url;

		}

		page.initHeader = function() {

			var janitor = u.ie(this.hN, "ul", {"class":"janitor"});
			u.ae(janitor, u.qs(".servicenavigation .front", page.hN));

			// prepare janitor text for animation
			var janitor_text = u.qs("li a", janitor);
			janitor_text.innerHTML = "<span>"+janitor_text.innerHTML.split("").join("</span><span>")+"</span>"; 
			page.hN.janitor_spans = u.qsa("span", janitor_text);

			var i, span, j, section, node;
			// set up navigation initial state
			for(i = 0; span = page.hN.janitor_spans[i]; i++) {

				if(i == 0) {
					u.ass(span, {
						"transform":"translate(-8px, 0)"
					});
				}
				else {
					u.ass(span, {
						"opacity":0,
						"transform":"translate(-8px, -30px)"
					});
				}
			}
			u.ass(janitor_text, {"opacity": 1});


			u.ae(page, u.qs(".servicenavigation", page.hN));


			var sections = u.qsa("ul.navigation > li", page.nN);
			if(sections) {
				for(i = 0; section = sections[i]; i++) {

					// nested navigation structure
					section.nodes = u.qsa("li", section);
					if(section.nodes.length) {

						// make individual navigation nodes clickable and collapse navigation on click to make transition look nicer
						for(j = 0; node = section.nodes[j]; j++) {
							u.ce(node, {"type":"link"});

							// set selected state
							if(u.hc(node, document.body.className)) {
								u.ac(node, "selected");
							}
						}


						section.header = u.qs("h3", section);
						if(section.header) {
							section.header.section = section;


							u.e.click(section.header);
							section.header.clicked = function() {

								if(this.section.is_open) {
									this.section.is_open = false;

									u.as(this.section, "height", this.offsetHeight+"px");
									u.saveNodeCookie(this.section, "open", 0, {"ignore_classvars":true});
									u.addExpandArrow(this);
								}
								else {
									this.section.is_open = true;

									u.as(this.section, "height", "auto");
									u.saveNodeCookie(this.section, "open", 1, {"ignore_classvars":true});
									u.addCollapseArrow(this);

								}
						
							}

							var state = u.getNodeCookie(section, "open", {"ignore_classvars":true});
							if(!state) {
								section.is_open = true;
							}
							section.header.clicked();

						}

					}
					else {

						u.ce(section, {"type":"link"});

						// set selected state
						if(u.hc(section, document.body.className)) {
							u.ac(section, "selected");
						}

					}

				}

			}


			// enable collapsed navigation
			u.e.hover(page.hN);
			page.hN.over = function() {

				u.t.resetTimer(this.t_navigation);

				u.a.transition(this, "all 0.3s ease-in-out");
				u.ass(this, {
					"width":"230px"
				});
				u.a.transition(page.nN, "all 0.3s ease-in");
				u.ass(page.nN, {
					"opacity":1
				});

				for(i = 0; span = page.hN.janitor_spans[i]; i++) {

					if(i == 0) {
						u.a.transition(span, "all 0.2s ease-in " + (i*50) + "ms");
						u.ass(span, {
							"transform":"translate(0, 0)"
						});
					}
					else {
						u.a.transition(span, "all 0.2s ease-in " + (i*50) + "ms");
						u.ass(span, {
							"opacity":1,
							"transform":"translate(0, 0)"
						});
					}
				}

			}

			page.hN.out = function() {

				u.rc(this, "over");

				var span, i;
				for(i = 0; span = page.hN.janitor_spans[i]; i++) {

					if(i == 0) {
						u.a.transition(span, "all 0.2s ease-in " + ((page.hN.janitor_spans.length-i)*50) + "ms");
						u.ass(span, {
							"transform":"translate(-8px, 0)"
						});
					}
					else {
						u.a.transition(span, "all 0.2s ease-in " + ((page.hN.janitor_spans.length-i)*50) + "ms");
						u.ass(span, {
							"opacity":0,
							"transform":"translate(-8px, -30px)"
						});
					}
				}

				u.a.transition(page.nN, "all 0.2s ease-in");
				u.ass(page.nN, {
					"opacity":0
				});

				u.a.transition(this, "all 0.2s ease-in-out 300ms");
				u.ass(this, {
					"width":"30px"
				});

			}
			
//			page.hN.t_navigation = u.t.setTimer(page.hN, "out", 500);

		}


		page.ready();
	}
}

u.e.addDOMReadyEvent(u.init)
