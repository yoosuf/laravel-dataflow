# Release Checklist

1. Run quality suite
- `composer lint`
- `composer analyse`
- `composer test`
- `composer mutation` (when infection dependencies are installed)

2. Verify docs
- README examples match current API
- upgrade notes updated
- changelog entry added

3. Verify package metadata
- composer requirements and suggestions accurate
- version bump and release tag aligned

4. Validate operational behavior
- queue + distributed export paths
- import error reports and status API

5. Publish
- tag release
- generate release notes
- announce upgrade highlights and migration cautions
