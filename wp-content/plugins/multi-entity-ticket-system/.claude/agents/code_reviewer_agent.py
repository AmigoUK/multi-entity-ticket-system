#!/usr/bin/env python3
"""
Code Reviewer Agent for Qwen Code
"""

import json
import os
import re
import subprocess
import sys
import traceback
from pathlib import Path
from typing import Dict, List, Optional, Tuple

class CodeReviewerAgent:
    """Expert code reviewer specializing in PHP, WordPress development, and software engineering best practices."""
    
    def __init__(self):
        self.name = "code-reviewer"
        self.description = "Code reviewer for pull requests, focusing on correctness, efficiency, readability, and best practices"
        self.model = "qwen"
        
    def parse_diff(self, diff_content: str) -> List[Dict]:
        """
        Parse git diff content into structured format.
        
        Args:
            diff_content (str): Raw git diff content
            
        Returns:
            List[Dict]: List of parsed file changes
        """
        files = []
        current_file = None
        current_hunk = None
        
        lines = diff_content.strip().split('\n')
        for line in lines:
            # Check for file header
            if line.startswith('diff --git'):
                # Extract file paths
                parts = line.split()
                if len(parts) >= 4:
                    old_file = parts[2][2:]  # Remove 'a/' prefix
                    new_file = parts[3][2:]  # Remove 'b/' prefix
                    
                    current_file = {
                        'old_file': old_file,
                        'new_file': new_file,
                        'changes': []
                    }
                    files.append(current_file)
                    
            # Check for hunk header
            elif line.startswith('@@') and current_file:
                # Parse hunk header: @@ -old_start,old_count +new_start,new_count @@
                hunk_match = re.match(r'@@ -(\d+),(\d+) \+(\d+),(\d+) @@', line)
                if hunk_match:
                    old_start, old_count, new_start, new_count = hunk_match.groups()
                    current_hunk = {
                        'old_start': int(old_start),
                        'old_count': int(old_count),
                        'new_start': int(new_start),
                        'new_count': int(new_count),
                        'lines': []
                    }
                    current_file['changes'].append(current_hunk)
                    
            # Check for line changes
            elif current_hunk and line.startswith(('+', '-', ' ')):
                line_type = line[0]  # '+', '-', or ' '
                line_content = line[1:] if len(line) > 1 else ''
                line_number = len(current_hunk['lines']) + current_hunk['new_start']
                
                current_hunk['lines'].append({
                    'type': line_type,
                    'content': line_content,
                    'line_number': line_number
                })
                
        return files
        
    def identify_critical_issues(self, files: List[Dict]) -> List[Dict]:
        """
        Identify critical issues (bugs, security vulnerabilities, performance problems).
        
        Args:
            files (List[Dict]): Parsed file changes
            
        Returns:
            List[Dict]: List of critical issues
        """
        issues = []
        
        for file_info in files:
            file_path = file_info['new_file']
            
            # Check each hunk for issues
            for hunk in file_info['changes']:
                for line_info in hunk['lines']:
                    if line_info['type'] != '+':  # Only check added/modified lines
                        continue
                        
                    line_content = line_info['content']
                    line_number = line_info['line_number']
                    
                    # Security issues
                    if 'eval(' in line_content and '$_' in line_content:
                        issues.append({
                            'type': 'critical',
                            'severity': 'high',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Potential code injection vulnerability',
                            'details': 'Using eval() with user input can lead to remote code execution',
                            'suggestion': 'Avoid using eval() with user input. Use proper validation and sanitization instead.'
                        })
                        
                    # SQL injection risks
                    if 'mysql_query(' in line_content and '$_' in line_content:
                        issues.append({
                            'type': 'critical',
                            'severity': 'high',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Potential SQL injection vulnerability',
                            'details': 'Direct MySQL queries with user input without proper escaping',
                            'suggestion': 'Use prepared statements or WordPress database abstraction layer with proper escaping.'
                        })
                        
                    # WordPress security issues
                    if 'wp_verify_nonce' not in line_content and 'nonce' in line_content.lower():
                        # Check if this is a form processing without nonce verification
                        if '$_POST' in line_content or '$_GET' in line_content:
                            issues.append({
                                'type': 'critical',
                                'severity': 'high',
                                'file': file_path,
                                'line': line_number,
                                'description': 'Missing nonce verification',
                                'details': 'Processing form data without verifying nonce for CSRF protection',
                                'suggestion': 'Add wp_verify_nonce() to validate form submissions.'
                            })
                            
                    # File inclusion vulnerabilities
                    if 'include(' in line_content or 'require(' in line_content:
                        if '$_' in line_content:
                            issues.append({
                                'type': 'critical',
                                'severity': 'high',
                                'file': file_path,
                                'line': line_number,
                                'description': 'Potential file inclusion vulnerability',
                                'details': 'Including files with user-controlled input without proper validation',
                                'suggestion': 'Validate and sanitize file paths before inclusion. Use fixed paths when possible.'
                            })
                            
        return issues
        
    def identify_minor_issues(self, files: List[Dict]) -> List[Dict]:
        """
        Identify minor issues (readability, style, maintainability improvements).
        
        Args:
            files (List[Dict]): Parsed file changes
            
        Returns:
            List[Dict]: List of minor issues
        """
        issues = []
        
        for file_info in files:
            file_path = file_info['new_file']
            
            # Check each hunk for issues
            for hunk in file_info['changes']:
                for line_info in hunk['lines']:
                    if line_info['type'] != '+':  # Only check added/modified lines
                        continue
                        
                    line_content = line_info['content']
                    line_number = line_info['line_number']
                    
                    # Long lines
                    if len(line_content) > 120:
                        issues.append({
                            'type': 'minor',
                            'severity': 'low',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Line too long',
                            'details': f'Line length ({len(line_content)} characters) exceeds recommended limit of 120 characters',
                            'suggestion': 'Break long lines into multiple lines for better readability.'
                        })
                        
                    # Missing comments on complex logic
                    if any(keyword in line_content.lower() for keyword in ['if (', 'for (', 'while (', 'foreach (']) and \
                       line_content.count('&&') > 2 or line_content.count('||') > 2:
                        issues.append({
                            'type': 'minor',
                            'severity': 'low',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Complex condition without comment',
                            'details': 'Complex conditional logic lacks explanatory comments',
                            'suggestion': 'Add comments to explain the purpose of complex conditions.'
                        })
                        
                    # Inconsistent spacing
                    if re.search(r'if\(', line_content) or re.search(r'for\(', line_content) or \
                       re.search(r'while\(', line_content) or re.search(r'foreach\(', line_content):
                        issues.append({
                            'type': 'minor',
                            'severity': 'low',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Missing space after control structure keyword',
                            'details': 'Missing space between control structure keyword and opening parenthesis',
                            'suggestion': 'Add space after control structure keywords (if, for, while, foreach).'
                        })
                        
        return issues
        
    def identify_exemplary_code(self, files: List[Dict]) -> List[Dict]:
        """
        Identify exemplary code worth praising.
        
        Args:
            files (List[Dict]): Parsed file changes
            
        Returns:
            List[Dict]: List of exemplary code highlights
        """
        highlights = []
        
        for file_info in files:
            file_path = file_info['new_file']
            
            # Check each hunk for good practices
            for hunk in file_info['changes']:
                for line_info in hunk['lines']:
                    if line_info['type'] != '+':  # Only check added/modified lines
                        continue
                        
                    line_content = line_info['content']
                    line_number = line_info['line_number']
                    
                    # Good security practices
                    if 'wp_verify_nonce' in line_content:
                        highlights.append({
                            'type': 'positive',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Good security practice',
                            'details': 'Proper nonce verification for CSRF protection'
                        })
                        
                    # WordPress coding standards
                    if 'sanitize_text_field' in line_content or 'esc_html__' in line_content:
                        highlights.append({
                            'type': 'positive',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Follows WordPress coding standards',
                            'details': 'Proper use of sanitization and internationalization functions'
                        })
                        
                    # Error handling
                    if 'is_wp_error' in line_content and 'wp_die' in line_content:
                        highlights.append({
                            'type': 'positive',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Good error handling',
                            'details': 'Proper error checking and user-friendly error messages'
                        })
                        
        return highlights
        
    def check_wordpress_standards(self, files: List[Dict]) -> List[Dict]:
        """
        Check for WordPress coding standards compliance.
        
        Args:
            files (List[Dict]): Parsed file changes
            
        Returns:
            List[Dict]: List of WordPress standards issues
        """
        issues = []
        
        for file_info in files:
            file_path = file_info['new_file']
            
            # Check each hunk for WordPress standards issues
            for hunk in file_info['changes']:
                for line_info in hunk['lines']:
                    if line_info['type'] != '+':  # Only check added/modified lines
                        continue
                        
                    line_content = line_info['content']
                    line_number = line_info['line_number']
                    
                    # Check for proper text domain
                    if '__(' in line_content or '_e(' in line_content:
                        if 'multi-entity-ticket-system' not in line_content:
                            issues.append({
                                'type': 'standards',
                                'severity': 'medium',
                                'file': file_path,
                                'line': line_number,
                                'description': 'Missing or incorrect text domain',
                                'details': 'Text domain should be "multi-entity-ticket-system" for proper internationalization',
                                'suggestion': 'Ensure all translation functions use the correct text domain.'
                            })
                            
                    # Check for proper escaping
                    if ('echo' in line_content and '$' in line_content) and \
                       ('esc_html' not in line_content and 'esc_attr' not in line_content):
                        issues.append({
                            'type': 'standards',
                            'severity': 'medium',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Missing output escaping',
                            'details': 'Output should be properly escaped to prevent XSS vulnerabilities',
                            'suggestion': 'Use esc_html(), esc_attr(), or other appropriate escaping functions.'
                        })
                        
                    # Check for proper sanitization
                    if ('$_POST[' in line_content or '$_GET[' in line_content) and \
                       ('sanitize' not in line_content and 'validate' not in line_content):
                        issues.append({
                            'type': 'standards',
                            'severity': 'medium',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Missing input sanitization',
                            'details': 'User input should be sanitized before use',
                            'suggestion': 'Use appropriate sanitization functions like sanitize_text_field().'
                        })
                        
        return issues
        
    def check_performance_issues(self, files: List[Dict]) -> List[Dict]:
        """
        Check for performance-related issues.
        
        Args:
            files (List[Dict]): Parsed file changes
            
        Returns:
            List[Dict]: List of performance issues
        """
        issues = []
        
        for file_info in files:
            file_path = file_info['new_file']
            
            # Check each hunk for performance issues
            for hunk in file_info['changes']:
                for line_info in hunk['lines']:
                    if line_info['type'] != '+':  # Only check added/modified lines
                        continue
                        
                    line_content = line_info['content']
                    line_number = line_info['line_number']
                    
                    # Database queries in loops
                    if any(query in line_content for query in ['$wpdb->query(', '$wpdb->get_', 'get_posts(']) and \
                       any(loop in line_content for loop in ['for (', 'while (', 'foreach (']):
                        issues.append({
                            'type': 'performance',
                            'severity': 'high',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Database query in loop',
                            'details': 'Performing database queries inside loops can cause performance issues',
                            'suggestion': 'Move database queries outside loops or use batch processing.'
                        })
                        
                    # Inefficient string concatenation
                    if line_content.count('.') > 10:
                        issues.append({
                            'type': 'performance',
                            'severity': 'medium',
                            'file': file_path,
                            'line': line_number,
                            'description': 'Inefficient string concatenation',
                            'details': 'Excessive use of dot concatenation can impact performance',
                            'suggestion': 'Consider using sprintf() or implode() for complex string building.'
                        })
                        
        return issues
        
    def review_code(self, diff_content: str) -> Dict:
        """
        Main code review method that orchestrates the entire process.
        
        Args:
            diff_content (str): Raw git diff content
            
        Returns:
            Dict: Complete code review analysis
        """
        review = {
            'files': [],
            'critical_issues': [],
            'minor_issues': [],
            'exemplary_code': [],
            'wordpress_standards': [],
            'performance_issues': [],
            'summary': {},
            'success': False
        }
        
        try:
            # 1. Parse the diff
            review['files'] = self.parse_diff(diff_content)
            
            # 2. Identify critical issues
            review['critical_issues'] = self.identify_critical_issues(review['files'])
            
            # 3. Identify minor issues
            review['minor_issues'] = self.identify_minor_issues(review['files'])
            
            # 4. Identify exemplary code
            review['exemplary_code'] = self.identify_exemplary_code(review['files'])
            
            # 5. Check WordPress standards
            review['wordpress_standards'] = self.check_wordpress_standards(review['files'])
            
            # 6. Check performance issues
            review['performance_issues'] = self.check_performance_issues(review['files'])
            
            # 7. Generate summary
            review['summary'] = {
                'total_files': len(review['files']),
                'critical_issues': len(review['critical_issues']),
                'minor_issues': len(review['minor_issues']),
                'exemplary_highlights': len(review['exemplary_code']),
                'wordpress_violations': len(review['wordpress_standards']),
                'performance_issues': len(review['performance_issues'])
            }
            
            review['success'] = True
            
        except Exception as e:
            review['error'] = str(e)
            review['traceback'] = traceback.format_exc()
            
        return review
        
    def format_review_report(self, review: Dict) -> str:
        """
        Format the code review into a human-readable report.
        
        Args:
            review (Dict): Complete code review analysis
            
        Returns:
            str: Formatted report
        """
        if not review.get('success', False):
            return f"Code review failed: {review.get('error', 'Unknown error')}"
            
        report = []
        report.append("# Code Review Report")
        report.append("")
        
        # Summary
        summary = review.get('summary', {})
        report.append("## Summary")
        report.append(f"- **Files Changed**: {summary.get('total_files', 0)}")
        report.append(f"- **Critical Issues**: {summary.get('critical_issues', 0)}")
        report.append(f"- **Minor Issues**: {summary.get('minor_issues', 0)}")
        report.append(f"- **Exemplary Code Highlights**: {summary.get('exemplary_highlights', 0)}")
        report.append(f"- **WordPress Standards Violations**: {summary.get('wordpress_violations', 0)}")
        report.append(f"- **Performance Issues**: {summary.get('performance_issues', 0)}")
        report.append("")
        
        # Critical Issues
        critical_issues = review.get('critical_issues', [])
        if critical_issues:
            report.append("## 🔴 Critical Issues (Must Fix)")
            for issue in critical_issues:
                report.append(f"### {issue.get('description', 'Unnamed Issue')}")
                report.append(f"- **File**: `{issue.get('file', 'Unknown')}:{issue.get('line', '?')}`")
                report.append(f"- **Severity**: {issue.get('severity', 'high').upper()}")
                report.append(f"- **Details**: {issue.get('details', 'No details provided')}")
                if issue.get('suggestion'):
                    report.append(f"- **Suggestion**: {issue.get('suggestion')}")
                report.append("")
                
        # Performance Issues
        performance_issues = review.get('performance_issues', [])
        if performance_issues:
            report.append("## 🟡 Performance Issues")
            for issue in performance_issues:
                report.append(f"### {issue.get('description', 'Unnamed Issue')}")
                report.append(f"- **File**: `{issue.get('file', 'Unknown')}:{issue.get('line', '?')}`")
                report.append(f"- **Severity**: {issue.get('severity', 'medium').upper()}")
                report.append(f"- **Details**: {issue.get('details', 'No details provided')}")
                if issue.get('suggestion'):
                    report.append(f"- **Suggestion**: {issue.get('suggestion')}")
                report.append("")
                
        # WordPress Standards
        wordpress_issues = review.get('wordpress_standards', [])
        if wordpress_issues:
            report.append("## 🟠 WordPress Standards Issues")
            for issue in wordpress_issues:
                report.append(f"### {issue.get('description', 'Unnamed Issue')}")
                report.append(f"- **File**: `{issue.get('file', 'Unknown')}:{issue.get('line', '?')}`")
                report.append(f"- **Severity**: {issue.get('severity', 'medium').upper()}")
                report.append(f"- **Details**: {issue.get('details', 'No details provided')}")
                if issue.get('suggestion'):
                    report.append(f"- **Suggestion**: {issue.get('suggestion')}")
                report.append("")
                
        # Minor Issues
        minor_issues = review.get('minor_issues', [])
        if minor_issues:
            report.append("## 🟢 Minor Issues (Consider Improving)")
            for issue in minor_issues:
                report.append(f"### {issue.get('description', 'Unnamed Issue')}")
                report.append(f"- **File**: `{issue.get('file', 'Unknown')}:{issue.get('line', '?')}`")
                report.append(f"- **Severity**: {issue.get('severity', 'low').upper()}")
                report.append(f"- **Details**: {issue.get('details', 'No details provided')}")
                if issue.get('suggestion'):
                    report.append(f"- **Suggestion**: {issue.get('suggestion')}")
                report.append("")
                
        # Exemplary Code
        exemplary_code = review.get('exemplary_code', [])
        if exemplary_code:
            report.append("## 💙 Exemplary Code Highlights")
            for highlight in exemplary_code:
                report.append(f"### {highlight.get('description', 'Good Practice')}")
                report.append(f"- **File**: `{highlight.get('file', 'Unknown')}:{highlight.get('line', '?')}`")
                report.append(f"- **Details**: {highlight.get('details', 'Well done!')}")
                report.append("")
                
        return "\n".join(report)

def main():
    """Main entry point for the code reviewer agent."""
    if len(sys.argv) < 2:
        print("Usage: python code_reviewer_agent.py <diff_file> [project_root]")
        sys.exit(1)
        
    diff_file = sys.argv[1]
    project_root = sys.argv[2] if len(sys.argv) > 2 else '.'
    
    # Read diff content
    try:
        with open(diff_file, 'r') as f:
            diff_content = f.read()
    except Exception as e:
        print(f"Error reading diff file: {e}")
        sys.exit(1)
        
    # Create code reviewer agent
    reviewer = CodeReviewerAgent()
    
    # Review the code
    review = reviewer.review_code(diff_content)
    
    # Print formatted report
    report = reviewer.format_review_report(review)
    print(report)
    
    # Also save to file
    import time
    report_file = f"code_review_{int(time.time())}.md"
    with open(report_file, 'w') as f:
        f.write(report)
        
    print(f"\nFull report saved to: {report_file}")

if __name__ == "__main__":
    main()