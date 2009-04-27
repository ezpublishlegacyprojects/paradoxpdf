/*
 * ParadoxPDF
 * author  : Mohamed Karnichi <http://www.tricinty.com>
 * version : 2.0
 *
 */

//Paradoxpdf  is free software; you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation; either version 2 of the License, or
//(at your option) any later version.
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

package com.tricinty;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FilenameFilter;
import java.io.IOException;
import java.io.OutputStream;
import java.util.Properties;

import org.xhtmlrenderer.pdf.ITextFontResolver;
import org.xhtmlrenderer.pdf.ITextRenderer;

import com.lowagie.text.DocumentException;
import com.lowagie.text.pdf.BaseFont;

public class ParadoxPDF
{

	public static final String FONTS_ECODING_PROP_FILENAME = "fonts_encoding.properties";
	public static final String DEFAULT_ENCODING = BaseFont.CP1250;
	private File inputFile;
	private String outputFilePath;
	private File fontDir;


	public static void main(String[] args)
	{
		// check arguments
		if (args.length < 2)
		{
			System.out.println("Usage : paradoxpdf <inputfile> <outputfile>");
			System.exit(1);
		}

		ParadoxPDF ppdf = new ParadoxPDF(args[0], args[1], args[2]);

		try
		{
			ppdf.export();

		} catch (DocumentException de)
		{
			System.err.println(de.getMessage());

		} catch (IOException ioe)
		{
			System.err.println(ioe.getMessage());
		}

	}

	public ParadoxPDF(String inputFilePath, String outputFilePath, String fontDirPath)
	{
		this.outputFilePath = outputFilePath;
		this.inputFile = new File(inputFilePath);

		if (!inputFile.exists())
		{
			System.err.println("ParadoxPDF ERROR: Input File not found: " + inputFilePath);
			System.exit(1);
		}

		//experimental

		if (fontDirPath != null)
		{

			this.fontDir = new File(fontDirPath);
			if (!fontDir.exists())
			{
				System.err.println("ParadoxPDF  WARNING : Font Director ynot found: " + fontDirPath);

			}
		}
	}

	public void export() throws DocumentException, IOException
	{

		String url = inputFile.toURI().toURL().toString();
		OutputStream os = new FileOutputStream(outputFilePath);
		final ITextRenderer renderer = new ITextRenderer();

		if (fontDir.exists())
		{
			ITextFontResolver resolver = renderer.getFontResolver();
			initFonts(resolver);
		}
		renderer.setDocument(url);
		renderer.layout();
		renderer.createPDF(os);
		os.close();

	}

	private void initFonts(ITextFontResolver resolver) throws DocumentException, IOException
	{
		String propertiesFilePath = "";
		if (fontDir.isDirectory())
		{
			propertiesFilePath = fontDir.getAbsolutePath() + '/' + FONTS_ECODING_PROP_FILENAME;
			Properties fontEncodingProps = loadProperties(propertiesFilePath);
			File[] files = fontDir.listFiles(new FilenameFilter()
			{
				public boolean accept(File dir, String name)
				{
					String lower = name.toLowerCase();
					return lower.endsWith(".otf") || lower.endsWith(".ttf");
				}
			});

			String[] settings;
			String encoding = DEFAULT_ENCODING;
			boolean embedded = false;
			String fontName;

			for (int i = 0; i < files.length; i++)
			{

				fontName = files[i].getName();

				if (fontEncodingProps.containsKey(fontName))
				{
					settings = fontEncodingProps.getProperty(fontName, DEFAULT_ENCODING + ";false").split(";");
					if (settings.length != 2)
					{
						System.err.println("ParadoxPDF Warning : Font Encoding properties Format invalid: " + fontEncodingProps.getProperty(fontName));

					} else
					{

						encoding = settings[0].trim();
						embedded = settings[1].trim().equalsIgnoreCase("true");
						System.err.println("ParadoxPDF adding font :" + "\n Font File Path :" + files[i].getAbsolutePath() + "\n Encoding " + encoding + "\n Embedded :" + embedded);
						resolver.addFont(files[i].getAbsolutePath(), encoding, embedded);

					}
				}
			}
		}
	}

	private Properties loadProperties(String propertiesFilePath)
	{
		Properties prop = new Properties();
		try
		{
			prop.load(new FileInputStream(propertiesFilePath));
		} catch (FileNotFoundException e)
		{
			System.err.println("ParadoxPDF Warning : Font Encoding properties file not found: " + propertiesFilePath);
		} catch (IOException e)
		{
			System.err.println("ParadoxPDF Warning : Error while reading Font Encoding properties file: " + propertiesFilePath);
		}
		return prop;
	}

}
