# Google Sheets Integration

`IQX Connect` now connects Google Sheets through a company-level Google OAuth flow. The admin saves the Google app credentials in the CRM, connects a Google account, then selects a spreadsheet and tab from the admin panel.

## 1. Create a Google OAuth web app

1. In Google Cloud, enable the Google Sheets API and Google Drive API.
2. Create an OAuth Client ID for a `Web application`.
3. Add this redirect URI from the CRM:

```text
https://your-domain.com/admin/google/callback
```

For local development, use your local app URL:

```text
http://127.0.0.1:8000/admin/google/callback
```

## 2. Save the Google app in IQX Connect

In `Admin > Data Sources`:

1. Open `Connect Google Sheets`.
2. Paste the `Client ID`.
3. Paste the `Client Secret`.
4. Click `Save Google App`.

This setup is stored per company in the database. No Google Sheets credentials are required in `.env`.

## 3. Connect the company Google account

Still in `Admin > Data Sources`:

1. Click `Connect Google`.
2. Sign in with the Google account that can access the spreadsheet.
3. Approve the requested Sheets and Drive access.

The connected account is stored per company so the CRM can:

- read spreadsheet rows during sync
- list spreadsheets and tabs
- write lead status or opportunity stage changes back to the same sheet row

## 4. Create the source from a spreadsheet tab

In `Admin > Data Sources`:

1. Click `Load Google Sheets`.
2. Select the spreadsheet.
3. Click `Load Tabs` if needed.
4. Select the tab.
5. Choose the source type: `Leads`, `Opportunities`, or `Reports`.
6. Click `Create Google Source`.

The source saves:

- spreadsheet ID
- sheet tab ID (`gid`)
- sheet tab title
- default header mapping for status write-back

## 5. Status write-back behavior

When a record comes from a Google Sheets API source, the CRM writes updates back to these headers by default:

- Leads: `Lead Status`
- Opportunities: `Sales Stage`

The sheet should keep headers on row `1`.

## Notes

- Uploaded CSV sources stay local and do not write back to Google Sheets.
- Public CSV or public Google Sheet export URLs can still be added manually as URL sources.
- Private Google Sheets should use the Google OAuth connect flow, not `.env` credentials.
