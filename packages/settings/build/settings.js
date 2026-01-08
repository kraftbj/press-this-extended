var __create = Object.create;
var __defProp = Object.defineProperty;
var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
var __getOwnPropNames = Object.getOwnPropertyNames;
var __getProtoOf = Object.getPrototypeOf;
var __hasOwnProp = Object.prototype.hasOwnProperty;
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, { get: all[name], enumerable: true });
};
var __copyProps = (to, from, except, desc) => {
  if (from && typeof from === "object" || typeof from === "function") {
    for (let key of __getOwnPropNames(from))
      if (!__hasOwnProp.call(to, key) && key !== except)
        __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
  }
  return to;
};
var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
  // If the importer is in node compatibility mode or this is not an ESM
  // file that has been converted to a CommonJS file using a Babel-
  // compatible transform (i.e. "__esModule" has not been set), then set
  // "default" to the CommonJS "module.exports" for node compatibility.
  isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: true }) : target,
  mod
));
var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

// packages/settings/src/settings.js
var settings_exports = {};
__export(settings_exports, {
  default: () => SettingsPage
});
module.exports = __toCommonJS(settings_exports);
var import_element = require("@wordpress/element");
var import_i18n = require("@wordpress/i18n");
var import_api_fetch = __toESM(require("@wordpress/api-fetch"));
var import_components = require("@wordpress/components");
var import_jsx_runtime = require("react/jsx-runtime");
var DEFAULT_SETTINGS = {
  media: true,
  text: true,
  blockquote: "<blockquote>%1$s</blockquote>",
  citation: '<p>Source: <em><a href="%1$s">%2$s</a></em></p>',
  parent: false,
  save_publish: "permalink",
  save_draft: "pt"
};
function SettingsPage() {
  const [settings, setSettings] = (0, import_element.useState)(DEFAULT_SETTINGS);
  const [isLoading, setIsLoading] = (0, import_element.useState)(true);
  const [isSaving, setIsSaving] = (0, import_element.useState)(false);
  const [notice, setNotice] = (0, import_element.useState)(null);
  (0, import_element.useEffect)(() => {
    loadSettings();
  }, []);
  const loadSettings = async () => {
    try {
      const response = await (0, import_api_fetch.default)({
        path: "/press-this-extended/v1/settings"
      });
      setSettings({ ...DEFAULT_SETTINGS, ...response });
    } catch (error) {
      setNotice({
        status: "error",
        message: (0, import_i18n.__)("Failed to load settings.", "press-this-extended")
      });
    } finally {
      setIsLoading(false);
    }
  };
  const saveSettings = async () => {
    setIsSaving(true);
    setNotice(null);
    try {
      await (0, import_api_fetch.default)({
        path: "/press-this-extended/v1/settings",
        method: "POST",
        data: settings
      });
      setNotice({
        status: "success",
        message: (0, import_i18n.__)("Settings saved.", "press-this-extended")
      });
    } catch (error) {
      setNotice({
        status: "error",
        message: (0, import_i18n.__)("Failed to save settings.", "press-this-extended")
      });
    } finally {
      setIsSaving(false);
    }
  };
  const updateSetting = (key, value) => {
    setSettings((prev) => ({ ...prev, [key]: value }));
  };
  if (isLoading) {
    return /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_components.Flex, { justify: "center", style: { padding: "40px" }, children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_components.Spinner, {}) });
  }
  return /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { className: "press-this-extended-settings wrap", children: [
    /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h1", { children: (0, import_i18n.__)("Press This Extended Settings", "press-this-extended") }),
    notice && /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
      import_components.Notice,
      {
        status: notice.status,
        isDismissible: true,
        onDismiss: () => setNotice(null),
        children: notice.message
      }
    ),
    /* @__PURE__ */ (0, import_jsx_runtime.jsxs)("div", { className: "pte-settings-cards", children: [
      /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_components.Card, { children: [
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_components.CardHeader, { children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", { children: (0, import_i18n.__)("Content Discovery", "press-this-extended") }) }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_components.CardBody, { children: [
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
            import_components.CheckboxControl,
            {
              __nextHasNoMarginBottom: true,
              label: (0, import_i18n.__)("Media Discovery", "press-this-extended"),
              help: (0, import_i18n.__)("Should Press This suggest media to add to a new post?", "press-this-extended"),
              checked: settings.media,
              onChange: (value) => updateSetting("media", value)
            }
          ),
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
            import_components.CheckboxControl,
            {
              __nextHasNoMarginBottom: true,
              label: (0, import_i18n.__)("Text Discovery", "press-this-extended"),
              help: (0, import_i18n.__)("Should Press This try to suggest a quote if you haven't preselected text?", "press-this-extended"),
              checked: settings.text,
              onChange: (value) => updateSetting("text", value)
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_components.Card, { children: [
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_components.CardHeader, { children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", { children: (0, import_i18n.__)("Formatting", "press-this-extended") }) }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_components.CardBody, { children: [
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
            import_components.TextareaControl,
            {
              __nextHasNoMarginBottom: true,
              label: (0, import_i18n.__)("Blockquote Formatting", "press-this-extended"),
              help: (0, import_i18n.__)("Use %1$s as a placeholder for the blockquote.", "press-this-extended"),
              value: settings.blockquote,
              onChange: (value) => updateSetting("blockquote", value)
            }
          ),
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
            import_components.TextareaControl,
            {
              __nextHasNoMarginBottom: true,
              label: (0, import_i18n.__)("Citation Formatting", "press-this-extended"),
              help: (0, import_i18n.__)("Use %1$s and %2$s as placeholders for the page URL and title, respectively.", "press-this-extended"),
              value: settings.citation,
              onChange: (value) => updateSetting("citation", value)
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_components.Card, { children: [
        /* @__PURE__ */ (0, import_jsx_runtime.jsx)(import_components.CardHeader, { children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)("h2", { children: (0, import_i18n.__)("Redirection", "press-this-extended") }) }),
        /* @__PURE__ */ (0, import_jsx_runtime.jsxs)(import_components.CardBody, { children: [
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
            import_components.CheckboxControl,
            {
              __nextHasNoMarginBottom: true,
              label: (0, import_i18n.__)("Redirect Parent Window", "press-this-extended"),
              help: (0, import_i18n.__)("Upon publishing or saving a draft, close the Press This popup and redirect the original tab.", "press-this-extended"),
              checked: settings.parent,
              onChange: (value) => updateSetting("parent", value)
            }
          ),
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
            import_components.SelectControl,
            {
              __nextHasNoMarginBottom: true,
              label: (0, import_i18n.__)("Upon Publishing...", "press-this-extended"),
              help: (0, import_i18n.__)("After publishing a post, you will be redirected to this location.", "press-this-extended"),
              value: settings.save_publish,
              options: [
                { label: (0, import_i18n.__)("Published Post", "press-this-extended"), value: "permalink" },
                { label: (0, import_i18n.__)("Standard Editor", "press-this-extended"), value: "editor" }
              ],
              onChange: (value) => updateSetting("save_publish", value)
            }
          ),
          /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
            import_components.SelectControl,
            {
              __nextHasNoMarginBottom: true,
              label: (0, import_i18n.__)("Upon Saving a Draft...", "press-this-extended"),
              help: (0, import_i18n.__)("After saving a draft, you will be redirected to this location.", "press-this-extended"),
              value: settings.save_draft,
              options: [
                { label: (0, import_i18n.__)("Remain in Press This", "press-this-extended"), value: "pt" },
                { label: (0, import_i18n.__)("Standard Editor", "press-this-extended"), value: "editor" }
              ],
              onChange: (value) => updateSetting("save_draft", value)
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ (0, import_jsx_runtime.jsx)("p", { className: "submit", children: /* @__PURE__ */ (0, import_jsx_runtime.jsx)(
        import_components.Button,
        {
          variant: "primary",
          onClick: saveSettings,
          isBusy: isSaving,
          disabled: isSaving,
          children: isSaving ? (0, import_i18n.__)("Saving...", "press-this-extended") : (0, import_i18n.__)("Save Settings", "press-this-extended")
        }
      ) })
    ] })
  ] });
}
//# sourceMappingURL=settings.js.map
