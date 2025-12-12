<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/">
    <html>
      <head>
        <meta charset="utf-8"/>
        <title>Отчёт: просроченные книги</title>
        <style>
          table { border-collapse: collapse; width: 100%; }
          th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
          th { background: #f4f4f4; }
        </style>
      </head>
      <body>
        <h1>Просроченные книги</h1>
        <xsl:if test="count(/overdue/item) = 0">
          <p>Нет просроченных книг.</p>
        </xsl:if>
        <xsl:if test="count(/overdue/item) &gt; 0">
          <table>
            <tr>
              <th>Loan ID</th>
              <th>Инв. номер</th>
              <th>Название</th>
              <th>Автор</th>
              <th>Читатель</th>
              <th>Дата выдачи</th>
              <th>Срок возврата</th>
              <th>Просрочено (дней)</th>
            </tr>
            <xsl:for-each select="/overdue/item">
              <tr>
                <td><xsl:value-of select="loan_id"/></td>
                <td><xsl:value-of select="inventory_number"/></td>
                <td><xsl:value-of select="title"/></td>
                <td><xsl:value-of select="author"/></td>
                <td><xsl:value-of select="reader_card"/></td>
                <td><xsl:value-of select="date_taken"/></td>
                <td><xsl:value-of select="due_date"/></td>
                <td><xsl:value-of select="days_overdue"/></td>
              </tr>
            </xsl:for-each>
          </table>
        </xsl:if>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
