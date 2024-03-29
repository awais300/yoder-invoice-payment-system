/*! protect 2021-01-25 */

function endsWith(e, t) { var n = e.length - t.length; return n >= 0 && e.lastIndexOf(t) === n }

function deepFreeze(e) { return Object.freeze(e), Object.getOwnPropertyNames(e).forEach(function(t) {!e.hasOwnProperty(t) || null === e[t] || "object" != typeof e[t] && "function" != typeof e[t] || Object.isFrozen(e[t]) || deepFreeze(e[t]) }), e }

function FieldControl(e) { e.onChange, e.id; const t = { blur: [], focus: [] }; return { label: new LabelControl(e), listen: function(e, n) { t[e].push(n) }, handle: function(e) { this.trigger(e) }, trigger: function(e) { t[e] && t[e].length > 0 && t[e].forEach(function(e) { e() }) } } }

function LabelControl(e) {
    let t = e.id,
        n = e.onChange;
    return { text: function(e) { return e && n(t + "LabelTextChange", e), e } }
}

function PTPaymentForm(e) {
    var t = this;
    const n = { "above the line": "label-top", horizontal: "label-inline", "below the line": "label-bottom" };
    this.options = e, this.HPF_CASPER_DOMAIN = "https://secure.paytrace.com", this.HPF_CASPER_URI = "/hpf/all", this.HPF_API_DOMAIN = "https://lev.paytrace.com", this.VERSION = "0.0.1", this.onStartCallback = null, this.onCompleteCallback = null, this.authorization = this.options.authorization, this.triggeringElement = null, this.styles = e.styles || {}, this.tokenizationPromise = null, this.cancelProcess = null, this.creditCard = deepFreeze(new FieldControl({ id: "CC", onChange: function(e, n) { t.onControlChange(e, n) } })), this.expiration = deepFreeze(new FieldControl({ id: "EXP", onChange: function(e, n) { t.onControlChange(e, n) } })), this.securityCode = deepFreeze(new FieldControl({ id: "SECCODE", onChange: function(e, n) { t.onControlChange(e, n) } })), this.onControlChange = function(e, t) { this.sendMessage({ command: e, payload: t }) }, this.process = function() {
        this.tokenizationPromise && this.cancelProcess("Tokenization Process Reinitiated. Cancelling Existing Response.");
        return this.tokenizationPromise = new Promise(function(e, t) {
            var n = this.timeoutId = setTimeout(function(e) { t("[HPF KIBOSH CLIENT] Timed out 10000ms. ") }, 1e4);
            this.cancelProcess = function(e) { clearInterval(n), t(e) }, this.completeProcess = function(t) { e(t) }
        }.bind(this)), this.tokenizeHPF(), this.tokenizationPromise
    }, this.onLoaded = function(e) { this.onLoadedCallback = e }, this.loadStyles = function(e) { e ? this.sendMessage({ command: "styleHPF", payload: e }) : this.sendMessage({ command: "styleHPF", payload: this.styles }) }, this.loadTheme = function(e) { Object.keys(n).indexOf(e) >= 0 ? this.sendMessage({ command: "setTheme", payload: n[e] }) : this.sendMessage({ command: "setTheme", payload: e }) }, this.availableThemes = function() { return Object.keys(n) }, this.loadInfo = function() { this.sendMessage({ command: "hpfLoadInfo", payload: this.authorization }) }, this.ping = function(e) { this.sendMessage({ command: "pingHPF", payload: e }) }, this.loadHPFFields = function() {
        if ("paytrace" === this.options.page) {
            var e = document.getElementById("pt_hpf_form");
            if (e) {
                const t = this.HPF_CASPER_DOMAIN + this.HPF_CASPER_URI;
                e.innerHTML = "<div><input id='pt_hpf_input' type='hidden'></input><iframe id='hpf_casper' src='' style='border: transparent;width: 100%;height: auto'> </iframe> </div>", document.getElementById("hpf_casper").src = t
            } else console.error("[PTKIBOSH] PayTrace HPF div 'pt_hpf_form' not found. Please ensure a div with id 'pt_hpf_form' is setup on the host site")
        }
    }, this.validateHPF = function() {
        if (this.validationPromise) return void console.warn("Waiting for previous validation to finish.");
        this.sendMessage({ command: "validateHPF", payload: null });
        return this.validationPromise = new Promise(function(e, t) { this.cancelValidationProcess = function(e) { t(e) }, this.completeValidationProcess = function(t) { e(t) }, this.validationTimeoutId = setTimeout(function() { t("Validation Call Timed out 10000ms.") }, 1e4) }.bind(this)), this.validationPromise
    }, this.tokenizeHPF = function() { this.sendMessage({ command: "tokenizeHPF", payload: this.authorization }) }, this.resetHPF = function() { this.sendMessage({ command: "resetHPF", payload: null }) }, this.handleHPFFieldEvents = function(e, t) { this[e].handle(t) }, this.handleValidation = function(e) { try { clearTimeout(this.validationTimeoutId), e.length > 0 ? this.cancelValidationProcess(e) : this.completeValidationProcess(e) } catch (e) { this.cancelValidationProcess(e) } finally { this.validationPromise = null } }, this.sendMessage = function(e) {
        var t = document.getElementById("hpf_casper").contentWindow,
            n = this.HPF_CASPER_DOMAIN;
        t.postMessage(e, n)
    }, this.receiveCasperMessage = function(e) {
        if (e.data && e.data.eventName) {
            if ("HostedPaymentCreated" === e.data.eventName && this.completeProcess && (this.completeProcess({ success: !0, message: e.data.data }), this.completeProcess = null, this.tokenizationPromise = null), "HostedPaymentCreationFailed" === e.data.eventName && this.cancelProcess && (this.cancelProcess({ success: !1, message: e.data.data.message, reason: e.data.data.errors }), this.cancelProcess = null, this.tokenizationPromise = null), "HostedPaymentFormLoadingComplete" === e.data.eventName && (this.loadInfo(), this.loadStyles(), this.onLoadedCallback && this.onLoadedCallback(e.data, this)), endsWith(e.data.eventName, "_HPFFieldEvent")) {
                const t = e.data.eventName.split("_");
                this.handleHPFFieldEvents(t[0], t[1])
            }
            if ("HostedPaymentFieldsValid" === e.data.eventName || "HostedPaymentFieldsInvalid" === e.data.eventName) {
                let t = e.data.data;
                this.handleValidation(t)
            }
        }
    }, this.receiveCasperMessage = this.receiveCasperMessage.bind(this), window.addEventListener("message", this.receiveCasperMessage, !1)
}
var PTPayment = function() {
    var e = null,
        t = function(t) {
            return new Promise(function(n, i) {
                try {
                    if (function(e) { return !!e.hasOwnProperty("authorization") }(t)) {
                        const i = function() { for (var e = {}, t = function(t) { for (var n in t) t.hasOwnProperty(n) && (e[n] = t[n]) }, n = 0; n < arguments.length; n++) t(arguments[n]); return e }(t, { page: "paytrace" });
                        (e = new PTPaymentForm(i)).onLoaded(function(e, t) { n(this) }), e.loadHPFFields()
                    } else i("Invalid Options")
                } catch (e) { i(e) }
            })
        };
    return {
        config: function(e) {},
        process: function() { return e.process() },
        validate: function(t) { return e.validateHPF().then(function(e) { t(e) }).catch(function(e) { t(e) }) },
        reset: function() { return e.resetHPF() },
        ping: function(t) { e.ping(t) },
        version: function() { return e.VERSION },
        style: function(t) { e.loadStyles(t) },
        theme: function(t) { e.loadTheme(t) },
        listAvailableThemes: function() { return e.availableThemes() },
        getControl: function(t) {
            if (e.hasOwnProperty(t)) return e[t];
            console.error("Invalid control name: " + t)
        },
        setup: function(e, n) {
            if ("undefined" == typeof Promise) {
                if ("undefined" === n) throw "Exception: Missing callback argument during setup. Your browser does not natively support promises. Use a different browser or contact site administrator.";
                ! function(e, t, n) {
                    const i = document.getElementById(n);
                    if (!i) {
                        const i = document.createElement("script");
                        i.src = e, i.id = n, document.body.appendChild(i), i.onload = function() { t && t() }
                    }
                    i && t && t()
                }("https://cdnjs.cloudflare.com/ajax/libs/bluebird/3.3.4/bluebird.min.js", function() { n && t.call(this, e).then(function(e) { n(e) }).catch(function(e) { console.error("unexpected error in creating payment link.") }) }.bind(this), "pt_bluebird")
            } else {
                if (!n) return t.call(this, e);
                t.call(this, e).then(function(e) { n(e) }).catch(function(e) { console.error("unexpected error in creating payment link.") })
            }
        }
    }
}();