/** @type {import('@docusaurus/types').DocusaurusConfig} */
module.exports = {
  title: "PHP Antimalware Scanner",
  tagline: "Dinosaurs are cool",
  url: "https://marcocesarato.github.io/PHP-Antimalware-Scanner/",
  baseUrl: "/PHP-Antimalware-Scanner/",
  onBrokenLinks: "throw",
  onBrokenMarkdownLinks: "warn",
  favicon: "img/favicon.ico",
  organizationName: "marcocesarato", // Usually your GitHub org/user name.
  projectName: "PHP-Antimalware-Scanner", // Usually your repo name.
  themeConfig: {
    navbar: {
      title: "PHP Antimalware Scanner",
      logo: {
        alt: "PHP Antimalware Scanner Logo",
        src: "img/logo.png",
      },
      items: [
        {
          type: "doc",
          docId: "intro",
          position: "left",
          label: "Documentation",
        },
        {
          href: "https://github.com/marcocesarato/PHP-Antimalware-Scanner",
          label: "GitHub",
          position: "right",
        },
      ],
    },
    footer: {
      style: "dark",
      links: [
        {
          title: "Links",
          items: [
            {
              label: "Open an issue",
              href: "https://github.com/marcocesarato/PHP-Antimalware-Scanner/issues",
            },
            {
              label: "Report a malware",
              href: "https://github.com/marcocesarato/PHP-Antimalware-Scanner/issues",
            },
            {
              label: "Stack Overflow",
              href: "https://stackoverflow.com/questions/tagged/PHP-Antimalware-Scanner",
            },
          ],
        },
        {
          title: "More",
          items: [
            {
              label: "GitHub",
              href: "https://github.com/marcocesarato/PHP-Antimalware-Scanner",
            },
          ],
        },
      ],
    },
  },
  presets: [
    [
      "@docusaurus/preset-classic",
      {
        docs: {
          routeBasePath: "/",
          sidebarPath: require.resolve("./sidebars.js"),
          editUrl:
            "https://github.com/marcocesarato/PHP-Antimalware-Scanner/edit/master/docs/",
        },
        theme: {
          customCss: require.resolve("./src/css/custom.css"),
        },
      },
    ],
  ],
  plugins: [
    // ... Your other plugins.
    [
      require.resolve("@easyops-cn/docusaurus-search-local"),
      {
        // ... Your options.
        // `hashed` is recommended as long-term-cache of index file is possible.
        hashed: true,
        indexPages: true,
        indexDocs: true,
        // For Docs using Chinese, The `language` is recommended to set to:
        // ```
        // language: ["en", "zh"],
        // ```
        // When applying `zh` in language, please install `nodejieba` in your project.
      },
    ],
  ],
};
