<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
<xsl:template match="/">
<html>
<head>
    <title>Sitemap - Rasprodaja.rs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .stats {
            background: #e8f0fe;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f5f5f5;
        }
        a {
            color: #667eea;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .priority {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .high {
            background: #d4edda;
            color: #155724;
        }
        .medium {
            background: #fff3cd;
            color: #856404;
        }
        .low {
            background: #f8d7da;
            color: #721c24;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .search {
            margin: 20px 0;
        }
        .search input {
            padding: 8px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🗺️ XML Sitemap - Rasprodaja.rs</h1>
    
    <div class="stats">
        <strong>📊 Statistika:</strong> Ukupno <xsl:value-of select="count(urlset/url)"/> URL-ova
    </div>
    
    <div class="search">
        <input type="text" id="search" placeholder="🔍 Pretraži URL..." onkeyup="filterTable()"/>
    </div>
    
    <table id="sitemapTable">
        <thead>
            <tr>
                <th>📍 URL</th>
                <th>⭐ Prioritet</th>
                <th>🔄 Učestalost</th>
                <th>📅 Datum</th>
            </tr>
        </thead>
        <tbody>
            <xsl:for-each select="urlset/url">
                <tr>
                    <td><a href="{loc}" target="_blank"><xsl:value-of select="loc"/></a></td>
                    <td>
                        <xsl:choose>
                            <xsl:when test="priority &gt;= 0.8">
                                <span class="priority high"><xsl:value-of select="priority"/></span>
                            </xsl:when>
                            <xsl:when test="priority &gt;= 0.5">
                                <span class="priority medium"><xsl:value-of select="priority"/></span>
                            </xsl:when>
                            <xsl:otherwise>
                                <span class="priority low"><xsl:value-of select="priority"/></span>
                            </xsl:otherwise>
                        </xsl:choose>
                    </td>
                    <td><xsl:value-of select="changefreq"/></td>
                    <td><xsl:value-of select="lastmod"/></td>
                </tr>
            </xsl:for-each>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Sitemap generisan automatski | <a href="/sitemap.xml">Prikaži izvorni XML</a></p>
    </div>
</div>

<script>
function filterTable() {
    const input = document.getElementById('search');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('sitemapTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const urlCell = rows[i].getElementsByTagName('td')[0];
        if (urlCell) {
            const urlText = urlCell.innerText.toLowerCase();
            if (urlText.includes(filter)) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}
</script>
</body>
</html>
</xsl:template>
</xsl:stylesheet>