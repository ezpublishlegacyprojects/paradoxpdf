Extension : paradoxpdf
Requires  : eZ Publish 4.x.x (not tested on 3.X but can be easily customised for)
Authors   : Mohamed Karnichi karnichi[at]gmail[dot]com


What is ParadoxPDF?
-------------------

ParadoxPDF is an ezpublish extension thant provides a realy easy way to serve your content as PDF files.
It'is platform independant, easy to use and performant.

Why ParadoxPDF?
---------------

I Love eZ Publish. As you know PDF support is deprecated. So we need a handy way to serve content as PDF files.
As I have left my current Employer, I have time to make some useful things :
ParadoxPDF is a simple and efficient way to generate pdf files from xhtml+CSS.


License
-------

This program is free software; you can redistribute it and/or
modify it under the terms of version 2.0  of the GNU General
Public License as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.


ParadoxPDF uses a java jar : paradoxpdf.jar built upon 'Flying Saucer' https://xhtmlrenderer.dev.java.net/
Flying saucer is a XML/XHTML/CSS 2.1 Renderer that supports a native PDF output support.

Paradoxpdf.jar is a wrapper application that can be used from command line. Is contains all dependencies.
and sources will be soon published on sourcefoge.net

The original idea for using 'Flying Saucer' to generate pdf contents was published by Joshua Marinacci to
http://today.java.net/pub/a/today/2007/06/26/generating-pdfs-with-flying-saucer-and-itext.html



ParadoxPDF features
-------------------

ParadoxPDF provides all features of Flying Saucer as :

    * Platform independent : 100% Java XML+CSS layout engine with native PDF output.
    * Strong support for the CSS 2.1 specification including extensions to better support paged media.
    * Support for XHTML.
    * Arbitrary elements may be replaced with custom content.
    * Some support for PDF specific features (for example, bookmarks and internal links). More coming soon.

In addition :
    * Easy : Prepare your designs just as any html design for the PDF View and let the magic happen
    * Multipage generation and page break-support (with custom header and footer)
    * Good performance (better than Apache FOP).
    * Cache : You can manage PDF caches as template cache-blocks look doc/doc.txt (cluster compatible)


Installation
------------

Read doc/install.txt
Look at the comments and sample code in design/templates/paradox_pdf_layout.tpl

Requirements
------------

The extension requires the Java Runtime Environment (JRE) version 1.4 or above
This can be obtained from the Java download pages. Follow the
installation instructions for JRE as described on the Java home pages.


Where to get more help
----------------------
- look in the extension doc folder
- look at project page/forum :http://projects.ez.no/paradoxpdf/forum


